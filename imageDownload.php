<?php


$import = 'hideawaysBlogLinks.csv';
$pageDivList = [];
$links = [];
//array of cuts for blog url
$blogURLs = ['blog-conservation-', 'blog-food-drink-', 'blog-homeowner-', 'blog-hot-off-the-press-', 'blog-whats-on-'];

require_once 'ScapeNDom.class.php';
require_once 'CSV.class.php';
require_once 'XML.class.php';


$pages = CSV::readCSVintoArray($import);
array_shift($pages); //Bin off the headers

$importer = new ScapeNDom();
$importer->multiplePage($pages);

echo "Scrape Done!".PHP_EOL;

$outputCSV = [];
$outputRedirectsCSV = [];
$allLinks = [];
$parsedLinks = [];
$ourLinks = [];
$missingLinks = [];
$imageURL = [];
$allImages = [];



foreach($importer->getPages() as $url => $page){
    $addedArray = [];

    //Work out new URL
    $afterDomain = explode(".co.uk/",$url);
    $afterDomain = $afterDomain[1];
    $afterDomain = str_replace('/', '-', $afterDomain);
    
    if(strpos($afterDomain, 'holiday-cottages-in') !== false){
        echo('first').PHP_EOL;
        $pageType = '';
    } elseif(strpos($afterDomain, 'cottages-in') !== false) {
        echo('second').PHP_EOL;
        $pageType = 'cottage-regions';
    } else {
        echo('third').PHP_EOL;
        $pageType = '';
    }
    
    $subPageType = '';
    $newURL = '/'.$afterDomain.'.html';

    $title = $importer->getTagByAttribute($url, "title", null, null)[0]['text'] ?? "Manor";
    $metas = $importer->getTagByAttribute($url, "meta", "name", "description")[0]['attributes']['content'] ?? "Manor";
    $h1 = $importer->getTagByAttribute($url, "article", null, null);
    $h1 = $h1[0]['children']['h1'][0]['text'];

    $postDate = $importer->getTagByAttribute($url, "span", 'class', 'article-meta__date');
    $postDate = $postDate[0]['text'];

    //$importer->removeH1s($url);

    $search = 0;

    $content = [];
    $contentText = null;
    
    switch($pageType){
        case "blog":
            $content[] = $importer->getTagByAttribute($url, "div", 'class', 'body-text');
            $search = true;
            break;
        case "collections":
            $content[] = $importer->getTagByAttribute($url, "div", 'ng-init', 'catslug = ');
            $search = true;
            break;
        case "about-us":
            $content[] = $importer->getTagByAttribute($url, "main");
            break;
        case "special-offers":
            $contentText = "&nbsp;";
            $search = true;
            break;
        case "main":
            $content[] = $importer->getTagByAttribute($url, "main", 'id', 'content');
            break;
        case "uk":
            $content[] = $importer->getTagByAttribute($url, "div", 'ng-init', 'catslug = ');
            $search = true;
            break;
        case "cottage-regions":
            $content[] = $importer->getTagByAttribute($url, "div", 'class', 'hero__description');
            $search = true;
            break;
        case "cottages":
            switch($subSubPageType){
                case "about-us.html":
                    $content[] = $importer->getTagByAttribute($url, "section","class","main-content");
                    break;
                case "video.html":
                    $content[] = $importer->getTagByAttribute($url, "body");
                    break;
                case "what-we-offer-owners.html":
                    $content[] = $importer->getTagByAttribute($url, "div","class","col-md-6 col-sm-12");
                    break;
                case "our-marketing.html":
                    $content[] = $importer->getTagByAttribute($url, "div","class","col-md-6 col-sm-12");
                    break;
                default:
                    $content[] = $importer->getTagByAttribute($url, "div","class","col-md-4 col-md-8");
            }

            break;
        case "points-of-interest":
            $h1 = $importer->getTagByAttribute($url, "h4", null,null)[0]['html'].$h1;
            $content[] = $importer->getTagByAttribute($url, "h2", 'style', 'color:white');
            $content[] = $importer->getTagByAttribute($url, "div", 'class', 'interest-container');
            break;
        case "help":
            $content[] = $importer->getTagByAttribute($url, "div", 'class', 'well');
            break;
        default:
            $content[] = $importer->getTagByAttribute($url, "div","class","blog-post__articles");
    }
    
    if(isset($contentText)){
        $textContent = $contentText;
    } else {
        $textContent = "";
    
        foreach($content as $contentItem) {
    
            
            $foundLinksBlock = [];
        
            if(!sizeof($contentItem)){
                echo "Content block empty - ".$afterDomain;
                continue;
            }
        
            $contentItem = $contentItem[0];
        
            if(isset($contentItem['html'])){
                $tempText = $contentItem['html'];
            }
        
            if (isset($contentItem['children'])) {
            
                $imageResultArray = [];
                $images = $importer->traverseAndSearch($contentItem['children'], "img", $imageResultArray, null, null);
            
                if ($images) {
                    foreach ($images as $img) {
                        if (isset($img['attributes']['ng-preload-src']) && !stristr($img['attributes']['ng-preload-src'],"//")) {
                            $imageReplace = $importer->downloadImage($url, $img['attributes']['ng-preload-src']);
                            $tempText = str_replace($img['attributes']['ng-preload-src'],$imageReplace,$tempText);
                        }
                    
                        if (isset($img['attributes']['src'])  && !stristr($img['attributes']['src'],"//")) {
                            $imageReplace = $importer->downloadImage($url, $img['attributes']['src']);
                            $tempText = str_replace($img['attributes']['src'],$imageReplace,$tempText);
                        }
                    }
                }
            }
        
            $textContent .= $tempText;
    
            $allLinks[] = $importer->traverseAndSearch($contentItem['children'], "a", $foundLinksBlock, 'href', 'https://www.helpfulholidays.co.uk/');
        }
    }

    
    //Link
    
    
    

    //TrimH1s

    /*
    $addedArray[0] = str_replace('https://www.manorcottages.co.uk', '', $url);
    $addedArray[1] = $newURL;
    $addedArray[2] = trim($title);
    $addedArray[3] = trim($metas);
    $addedArray[4] = trim($h1);
    $addedArray[5] = trim(preg_replace( "/\r|\n/", "", str_replace(",","&#44;", str_replace("'","&#39;",$textContent))));
    */
    
    $addedArray[0] = $newURL;
    $addedArray[1] = $postDate;
    $addedArray[2] = 'post';
    $addedArray[3] = 'publish';
    $addedArray[4] = $h1;
    $addedArray[5] = $textContent;
    //$addedArray[5] = trim(preg_replace( "/\r|\n/", "", str_replace(",","&#44;", str_replace("'","&#39;", $textContent))));
    $addedArray[6] = 'news';
    $addedArray[7] = 'closed';
    $addedArray[8] = 'closed';
    $addedArray[9] = trim($title);
    $addedArray[10] = trim($metas);
    ///Collections Post Proc.
    
    preg_match_all('/< *img[^>]*src *= *["\']?([^"\']*)/i', $addedArray[5], $imageURL);
    var_dump($imageURL);
    array_push($allImages, $imageURL);
}
    
function get_http_response_code($url) {
    $headers = get_headers($url);
    return substr($headers[0], 9, 3);
}



$outputCSV = [];
$missingImages = [];
foreach($allImages as $singlePage) {
    $imageURLs = $singlePage[1];
    foreach($imageURLs as $imageURL) {
        $imageURL = str_replace('?ixlib=rails-2.1.4&amp;h=360&amp;w=640', '', $imageURL);
        $imageURL = str_replace('?ixlib=rails-2.1.4', '', $imageURL);
        $image_name = explode('/', $imageURL);
        $imageCount = count($image_name) - 1;
        $image_name = $image_name[$imageCount];
        if(strpos($image_name, '?') !== false) {
            $exploaded_image_name = explode('?', $image_name);
            $image_name = $exploaded_image_name[0];
            $exploaded_image_name = explode('?', $image_name);
            $image_name = $exploaded_image_name[0];
        }
    
        if(get_http_response_code($imageURL) != "200"){
            $missingImages[] = [
                'image_url' => $imageURL,
                'image_name' => $image_name
            ];
            echo "error getting $image_name from $imageURL".PHP_EOL;
        }else{
            $image = file_get_contents($imageURL);
            $outputCSV[] = [
                'image_url' => $imageURL,
                'image_name' => $image_name
            ];
            file_put_contents("hideaways_blog_images/$image_name", $image);
            echo("Downloaded $image_name").PHP_EOL;
        }
    }
}

var_dump($missingImages);
echo "Writing File".PHP_EOL;
CSV::writeArrayintoCSV($outputCSV,'HideawaysBlogImages.csv');
CSV::writeArrayintoCSV($missingImages,'HideawaysBlogMissingImages.csv');







