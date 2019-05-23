<?php
    
    require_once 'ScapeNDom.class.php';
    require_once 'CSV.class.php';
    //$properties = CSV::readCSVintoArray('property.csv');
    
    //$links = CSV::readCSVintoArray('cchLinksToReplace.csv');
    //$links = array_reverse($links);
    //array_shift($pages);
    
    $contents = CSV::readCSVintoArray('manorScrape.csv');
    
    /*
    foreach($links as $link)
    {
        $counts[] = strlen($link[0]);
    }
    array_multisort($counts, $links);
    
    $links = array_reverse($links);
    $letters = [
        'a', 'b', 'c', 'd', 'e', 'f', 'g', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'w', 'x', 'y', 'z'
    ];
    */
    /*
    foreach($contents as &$content) {
        foreach($links as $link) {
            if(substr($link[0], -1) == '/') {
                $link[0] = substr($link[0], 0, -1);
            }
            if(strpos($content[5], $link[0])) {
                echo "Trying to replace ".$link[0]." with ".$link[1]." on ".$content[1].PHP_EOL;
                $content[5] = str_replace($link[0], $link[1], $content[5]);
            }
        }
    }
    */
    
    $links_type = array();
    
    foreach($contents as &$content) {
        
        if(in_array($content[1], $links_type)) {
        
        } else {
            foreach($links_type as $link_type) {
                if(strpos($content[1], $link_type) == true) {
                    echo("CHUJ");
                }
            }
            array_push($links_type, $content[1]);
        }
        
        
        
        /*
        foreach($links as $link) {
            if(strpos($content[5], 'http://www.cornishcottageholidays.co.uk'.$link[0])) {
                $content[5] = str_replace('http://www.cornishcottageholidays.co.uk'.$link[0], $link[1], $content[5]);
            }
        }
        */
        /*
        if(strpos($content[5], 'https://res.cloudinary.com/helpful-holidayscollections.html/')) {
            $content[5] = str_replace('https://res.cloudinary.com/helpful-holidayscollections.html/', 'https://res.cloudinary.com/helpful-holidays/collections/', $content[5]);
        }
        if(strpos($content[5], 'https://res.cloudinary.com/helpful-holidays/image/uploadblog.html/')) {
            $content[5] = str_replace('https://res.cloudinary.com/helpful-holidays/image/uploadblog.html/', 'https://res.cloudinary.com/helpful-holidays/image/upload/blog/', $content[5]);
        }
        if(strpos($content[5], 'collections.html-')) {
            $content[5] = str_replace('collections.html-', 'collections-', $content[5]);
        }
        
        foreach($letters as $letter) {
            if(strpos($content[5], 'href="'.$letter)) {
                echo "I've fount you! You bastard!!! ".PHP_EOL;
                $content[5] = str_replace('href="'.$letter, 'href="/'.$letter, $content[5]);
            }
        }
        */
    }
    
    var_dump($links_type);
    
    
    
    
    //echo "Writing File".PHP_EOL;
    //CSV::writeArrayintoCSV($contents, 'cornishScrapeUpdated.csv');
    
