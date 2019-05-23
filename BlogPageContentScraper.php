<?php


$import = 'hideawaysBlogLinks.csv';
$pageDivList = [];
$links = [];
//array of cuts for blog url
$blogURLs = ['blog-conservation-', 'blog-food-drink-', 'blog-homeowner-', 'blog-hot-off-the-press-', 'blog-whats-on-'];

require_once 'ScapeNDom.class.php';
require_once 'CSV.class.php';
require_once 'XML.class.php';
$properties = CSV::readCSVintoArray('property.csv');
array_shift($properties); //Bin off headers

$pages = CSV::readCSVintoArray('hideawaysBlogLinks.csv');
array_shift($pages); //Bin off the headers

$importer = new ScapeNDom();
$importer->multiplePage($pages);

$manorRedirects = CSV::readCSVintoArray('manor301.csv');
$hideawaysRedirects = CSV::readCSVintoArray('hideaways301.csv');

echo "Scrape Done!".PHP_EOL;

$outputCSV = [];
$outputRedirectsCSV = [];
$allLinks = [];
$parsedLinks = [];
$ourLinks = [];
$missingLinks = [];


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
    //$afterDomainItems  = explode("/",$afterDomain[0]);
    //var_dump($afterDomainItems);
    /*
    $lastItem = array_pop($afterDomainItems); //Remove ghost item from the end of the list.
    
    if(trim($lastItem) != ""){
        $afterDomainItems[] = $lastItem;
    }

    if($afterDomainItems){
        $pageType = $afterDomainItems[0];

        if(isset($afterDomainItems[1])){
            $subPageType = $afterDomainItems[1];
        }else{
            $subPageType = "";
        }

        if(isset($afterDomainItems[2])){
            $subSubPageType = $afterDomainItems[2];
        }else{
            $subSubPageType = "";
        }
        
        if(strpos(end($afterDomainItems), '.html')) {
            $newURL  = implode("-",$afterDomainItems);
        } else {
            $newURL  = implode("-",$afterDomainItems).".html";
            foreach($blogURLs as $blogURL) {
                if(strpos($newURL, $blogURL) !== false) {
                    $newURL = str_replace($blogURL, '', $newURL);
                }
            }
        }
        
    }else{
        continue;
    }
    */
    //if($subSubPageType == "home-owners-blog.html"){
    //    $outputRedirectsCSV[] = [$afterDomain, "/blog",301];
    //    continue;
    //}

    $title = $importer->getTagByAttribute($url, "title", null, null)[0]['text'] ?? "Manor";
    $metas = $importer->getTagByAttribute($url, "meta", "name", "description")[0]['attributes']['content'] ?? "Manor";
    //$h1 = $importer->getTagByAttribute($url, "h1", null, null)[0]['text'] ?? "Manor";
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
            $content[] = $importer->getTagByAttribute($url, "div","class","body-text");
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
    
    $addedArray[9] = trim(preg_replace( "/\r|\n/", "", str_replace(",","&#44;", str_replace("'","&#39;",$addedArray[9]))));
    $addedArray[10] = trim(preg_replace( "/\r|\n/", "", str_replace(",","&#44;", str_replace("'","&#39;",$addedArray[10]))));
    $addedArray[4] = trim(preg_replace( "/\r|\n/", "", str_replace(",","&#44;", str_replace("'","&#39;",$addedArray[4]))));
    //$addedArray[5] = trim(preg_replace("/\r|\n/", "", $addedArray[5]));
    $addedArray[5] = trim(preg_replace( "/\r|\n/", "", str_replace(",","&#44;", str_replace("'","&#39;",$addedArray[5]))));
    $addedArray[5] = str_replace("’", '&#8217;', str_replace("‘", '&#8216;', $addedArray[5]));
    $addedArray[5] = str_replace('“', '&#8220;', str_replace("”", "&#8221;", $addedArray[5]));
    $addedArray[5] = str_replace('£', '&#163;', $addedArray[5]);
    $addedArray[5] = str_replace('á', '&#224;', str_replace('í', '&#237;', str_replace('é', '&#233;', $addedArray[5])));
    $addedArray[5] = str_replace('–', '&#8211;', str_replace('—', '&#8212;', $addedArray[5]));
    $addedArray[4] = str_replace("’", '&#8217;', str_replace("‘", '&#8216;', $addedArray[4]));
    $addedArray[9] = str_replace("’", '&#8217;', str_replace("‘", '&#8216;', $addedArray[9]));
    $addedArray[10] = str_replace("’", '&#8217;', str_replace("‘", '&#8216;', $addedArray[10]));
    $addedArray[5] = trim(preg_replace('\'<h1(.*?)>(.*?)</h1>\'', '', $addedArray[5]));
    $addedArray[5] = preg_replace('/<style[^>]*>([\s\S]*?)<\/style[^>]*>/', '', $addedArray[5]);
    $addedArray[5] = preg_replace('/<form[^>]*>([\s\S]*?)<\/form[^>]*>/', '', $addedArray[5]);
    $addedArray[5] = trim(str_replace('<a href="https://www.manorcottages.co.uk/', '<a href="/', $addedArray[5]));
    $addedArray[5] = trim(str_replace('<a href="https://www.hideaways.co.uk/', '<a href="/', $addedArray[5]));
    
    //foreach($properties as $property) {
    //    $property[0] = str_replace('https://www.hideaways.co.uk', '', $property[0]);
    //    if(strpos($addedArray[5], $property[0]) == true){
    //        $addedArray[5] = str_replace($property[0], $property[1], $addedArray[5]);
    //        $propertiesReplaced[] = [$property[0], $property[1]];
    //        echo("Changed $property[0] to $property[1] on $addedArray[1]".PHP_EOL);
    //    }
    //}
    //End of collections Post Proc.

    if($textContent == ""){
        echo "We have a missing content block on ".$url.PHP_EOL;
    }
    //var_dump($addedArray);
    $outputCSV[] = $addedArray;

    //$outputRedirectsCSV[] = [$addedArray[0],$newURL,301];
    
}

// Replace known landing page links with new known page links.
foreach($outputCSV as $singleCSV) {
    foreach($outputCSV as $CSVLinks) {
        if(strpos($singleCSV[5], $CSVLinks[0])) {
            str_replace($CSVLinks[0], $CSVLinks[1], $singleCSV[5]);
            echo("Changed ".$CSVLinks[0]." to ".$CSVLinks[1]." in".$singleCSV[4].PHP_EOL);
        }
    }
}
    
$manorRedirects = CSV::readCSVintoArray('manor301.csv');
$hideawaysRedirects = CSV::readCSVintoArray('hideaways301.csv');

foreach($manorRedirects as $manorRedirect) {
    foreach($outputCSV as $CSVContent) {
        if(strpos($CSVContent[5], $manorRedirect[0])) {
            str_replace($manorRedirect[0], $manorRedirect[1], $CSVContent[5]);
            echo("Changed ".$manorRedirect[0]." to ".$manorRedirect[1]." in".$CSVContent[0].PHP_EOL);
        }
    }
}

foreach($hideawaysRedirects as $hideawaysRedirect) {
    foreach($outputCSV as $CSVContent) {
        if(strpos($CSVContent[5], $hideawaysRedirect[0])) {
            str_replace($hideawaysRedirect[0], $hideawaysRedirect[1], $CSVContent[5]);
            echo("Changed ".$hideawaysRedirect[0]." to ".$hideawaysRedirect[1]." in".$CSVContent[0].PHP_EOL);
        }
    }
}



//GENERATE MISSING LINKS

foreach($allLinks as $page) {
    foreach($page as $link) {
        $parsedLink = str_replace("https://www.helpfulholidays.co.uk",'', $link['attributes']['href']);
        if(substr($parsedLink, -1) != '/' && substr($parsedLink, -5) != '.html') {
            $parsedLink = $parsedLink.'/';
        }
        //ignore gallery and cottage pages
        $parsedLinks[] = $parsedLink;
    }
}

foreach($outputCSV as $CSVLink) {
    if(substr($CSVLink[0], -1) != '/' && substr($CSVLink[0], -5) != '.html') {
        $CSVLink[0] = $CSVLink[0].'/';
    }
    $ourLinks[] = $CSVLink[0];
}

$parsedLinks = array_unique($parsedLinks);
foreach($parsedLinks as $parsedLink) {
    if(in_array($parsedLink, $ourLinks) == false) {
        $missingLinks[] = $parsedLink;
    }
}

$missingLinksOutput = [];
for($x = 0; $x < count($missingLinks); $x++) {
    $missingLinksOutput[$x][0] = 'https://www.helpfulholidays.co.uk'.$missingLinks[$x];
    if(strpos($missingLinksOutput[$x][0], '/holiday-cottages/') !== false) {
        unset($missingLinksOutput[$x]);
    }
}

//remove property missing links
foreach($properties as $property) {
    foreach($missingLinksOutput as $missingLink) {
        if(strpos($missingLink[0], $property[0]) == true) {
            echo('true'.PHP_EOL);
        }
    }
}

echo "Writing File".PHP_EOL;
CSV::writeArrayintoCSV($outputCSV,'manorBlogScrape.csv');
//XML::signArrayToXML($outputCSV,'manorXML.txt');
//echo "Writing Redirects File".PHP_EOL;
//CSV::writeArrayintoCSV($outputRedirectsCSV,'hideawaysBlog301.csv');

//echo "Writing Propsreplaced File".PHP_EOL;
//CSV::writeArrayintoCSV($propertiesReplaced,"PropsReplaced.csv");

//echo "Writing File Missing Links".PHP_EOL;
//$missingLinksCSV = fopen('hideaways-blog-missing-links.csv', 'w');
//foreach($missingLinksOutput as $link) {
//    fputcsv($missingLinksCSV, $link);
//}
//fclose($missingLinksCSV);

//var_dump($properties);

