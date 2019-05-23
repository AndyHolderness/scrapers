<?php

$import = 'coastandcountry.csv';
$pageDivList = [];
$links = [];
//array of cuts for blog url
$blogURLs = ['blog-conservation-', 'blog-food-drink-', 'blog-homeowner-', 'blog-hot-off-the-press-', 'blog-whats-on-'];

require_once 'ScapeNDom.class.php';
require_once 'CSV.class.php';
require_once 'XML.class.php';
$properties = CSV::readCSVintoArray('property.csv');
array_shift($properties); //Bin off headers

$pages = CSV::readCSVintoArray($import);
//array_shift($pages); //Bin off the headers

$importer = new ScapeNDom();
$importer->multiplePage($pages);

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
    $afterDomain = str_replace('/', '-', $afterDomain[1]);

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
    $afterDomainItems  = explode("/",$afterDomain[0]);
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

    $title = $importer->getTagByAttribute($url, "title", null, null)[0]['text'] ?? "Coast";
    $metas = $importer->getTagByAttribute($url, "meta", "name", "description")[0]['attributes']['content'] ?? "Coast";
    $h1 = $importer->getTagByAttribute($url, "h1", null, null)[0]['text'] ?? "Coast";
    if($h1 == 'Coast') {
        $h1 = $importer->getTagByAttribute($url, "h3", null, null)[0]['text'] ?? 'Coast';
    }

    //$importer->removeH1s($url);

    $search = 0;

    $content = [];
    $contentText = null;
echo "Getting Content for " . $title . "<br />";
    $articleContent = $importer->getTagByAttribute($url, "div","class","location-guide__articles");
    if(!empty($articleContent)) {
        $pageType = 'article';
    }
    echo($pageType).PHP_EOL;
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
        case "article":
            $content[] = $importer->getTagByAttribute($url, "div","class","location-guide__articles");
            break;
        default:
            //$content[] = $importer->getTagByAttribute($url, "div","class","navbar-collapse");
            $content[] = $importer->getTagByAttribute($url, "div","class","body-text");
            //array_push($content, $importer->getTagByAttribute($url, "div","class","body-text"));
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

            $allLinks[] = $importer->traverseAndSearch($contentItem['children'], "a", $foundLinksBlock, 'href', 'https://www.coastandcountry.co.uk/');
        }
    }
    //Link

    //TrimH1s


    $addedArray[0] = str_replace('https://www.coastandcountry.co.uk', '', $url);
    $addedArray[1] = $newURL;
    $addedArray[2] = trim($title);
    $addedArray[3] = trim($metas);
    $addedArray[4] = trim($h1);
    $addedArray[5] = trim(preg_replace( "/\r|\n/", "", str_replace(",","&#44;", str_replace("'","&#39;",$textContent))));

    ///Collections Post Proc.
    $addedArray[2] = trim(preg_replace( "/\r|\n/", "", str_replace(",","&#44;", str_replace("'","&#39;",$addedArray[2]))));
    $addedArray[3] = trim(preg_replace( "/\r|\n/", "", str_replace(",","&#44;", str_replace("'","&#39;",$addedArray[3]))));
    $addedArray[4] = trim(preg_replace( "/\r|\n/", " ", str_replace(",","&#44;", str_replace("'","&#39;",$addedArray[4]))));
    $addedArray[4] = str_replace('  ', ' ', str_replace('   ', ' ', $addedArray[4]));
    if(strpos($addedArray[0], 'collections/') || strpos($addedArray[0], 'uk')){
        $addedArray[5] = str_replace('<a href="" ng-click="hideCategoryBanner()" class="pull-right icon-cancel-circle" style="font-size: 21px; color: white;"/>', "", $addedArray[5]);
    }

    $pricing_gallery_strings = ['#/gallery/', '#/gallery', '#/prices/', '#/prices'];
    $addedArray[5] = preg_replace('/(<[^>]+) style=".*?"/i', '$1', $addedArray[5]);
    $addedArray[5] = preg_replace('\'<h1(.*?)>(.*?)</h1>\'', '$1', $addedArray[5]);
    $addedArray[5] = preg_replace("/<img[^>]+\>/i", "", $addedArray[5]);
    $addedArray[5] = preg_replace("/<div[^>]+\>/i", "", $addedArray[5]);
    $addedArray[5] = str_replace('</div>', '', $addedArray[5]);
    $addedArray[5] = str_replace('<br/>', '', $addedArray[5]);
    $addedArray[5] = str_replace('<a href="https://www.coastandcountry.co.uk/', '<a href="/', $addedArray[5]);
    $addedArray[5] = str_replace('<a href="http://www.coastandcountry.co.uk/', '<a href="/', $addedArray[5]);
    $addedArray[5] = str_replace("’", '&#8217;', str_replace("‘", '&#8216;', $addedArray[5]));
    $addedArray[5] = str_replace('“', '&#8220;', str_replace("”", "&#8221;", $addedArray[5]));
    $addedArray[5] = str_replace('£', '&#163;', $addedArray[5]);
    $addedArray[5] = str_replace('á', '&#224;', str_replace('í', '&#237;', str_replace('é', '&#233;', $addedArray[5])));
    $addedArray[5] = str_replace('–', '&#8211;', str_replace('—', '&#8212;', $addedArray[5]));
    $addedArray[5] = str_replace('<div class="image-left"/>', '', $addedArray[5]);
    $addedArray[5] = str_replace('<div class="image-right"/>', '', $addedArray[5]);

    if(strpos($addedArray[4], 'Where to drink') !== false) {
        $addedArray[4] = str_replace('Where to drink', '&#34;Where to drink&#34;', $addedArray[4]);
    }
    if(strpos($addedArray[4], 'What to do') !== false) {
        $addedArray[4] = str_replace('What to do', '&#34;What to do&#34;', $addedArray[4]);
    }
    if(strpos($addedArray[4], 'Where to eat') !== false) {
        $addedArray[4] = str_replace('Where to eat', '&#34;Where to eat&#34;', $addedArray[4]);
    }

    $opened_divs = substr_count($addedArray[5], '<div');
    $closed_divs = substr_count($addedArray[5], '</div>');
    $opened_paragraphs = substr_count($addedArray[5], '<p');
    $closed_paragraphs = substr_count($addedArray[5], '</p>');

    if($opened_divs !== $closed_divs) {
        echo('Unclosed div in '.$addedArray[1].' ').PHP_EOL;
    }

    if($opened_paragraphs !== $closed_paragraphs) {
        echo('Unclosed paragraph in '.$addedArray[1].' ').PHP_EOL;
    }

    foreach($properties as $property) {
        $property[0] = str_replace('https://www.coastandcountry.co.uk', '', $property[0]);
        if(strpos($addedArray[5], $property[0]) == true){
            $addedArray[5] = str_replace($property[0], $property[1], $addedArray[5]);
            $propertiesReplaced[] = [$property[0], $property[1]];
            echo("Changed $property[0] to $property[1] on $addedArray[1]".PHP_EOL);
        }
    }
    //End of collections Post Proc.

    if($textContent == ""){
        echo "We have a missing content block on ".$url.PHP_EOL;
    }

    $outputCSV[] = $addedArray;

    $outputRedirectsCSV[] = [$addedArray[0],$newURL,301];
}

// Replace known landing page links with new known page links.
$linksArray = [];
$sortedLinksArray = [];

foreach($outputCSV as $link) {
    $linksArray[$link[0]] = $link[1];
}
usort($linksArray, function($a, $b) {
    return strlen($b) <=> strlen($a);
});

foreach($linksArray as $linkToCheck) {
    foreach($outputCSV as $linkToAdd) {
        if($linkToCheck == $linkToAdd[1]) {
            $sortedLinksArray[$linkToAdd[0]] = $linkToCheck;
        }
    }
}

for($x = 0; $x < count($outputCSV); $x++) {
    foreach($sortedLinksArray as $oldLink=>$newLink) {
        if(strpos($outputCSV[$x][5], $oldLink.'"')) {
            $outputCSV[$x][5] = str_replace($oldLink.'"', $newLink.'"', $outputCSV[$x][5]);
            echo("Changed ".$oldLink." to ".$newLink." in ".$outputCSV[$x][1].PHP_EOL);
        }
    }
}

//GENERATE MISSING LINKS
foreach($allLinks as $page) {
    foreach($page as $link) {
        $parsedLink = str_replace("https://www.coastandcountry.co.uk",'', $link['attributes']['href']);
        if(substr($parsedLink, -1) != '/' && substr($parsedLink, -5) != '.html') {
            $parsedLink = $parsedLink.'/';
        }
        // ignore gallery and cottage pages
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
    $missingLinksOutput[$x][0] = 'https://www.coastandcountry.co.uk'.$missingLinks[$x];
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
CSV::writeArrayintoCSV($outputCSV,'coastandcountryScrape.csv');

echo "Writing Redirects File".PHP_EOL;
CSV::writeArrayintoCSV($outputRedirectsCSV,'coastandcountry301.csv');

echo "Writing File Missing Links".PHP_EOL;
$missingLinksCSV = fopen('coastandcountryMissingLinks.csv', 'w');
foreach($missingLinksOutput as $link) {
    fputcsv($missingLinksCSV, $link);
}
fclose($missingLinksCSV);

//var_dump($properties);
