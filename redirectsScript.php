<?php
$brand = 312; // 7 for cornish, 8 for devonshire 9 for lakes 10 for helpful
$environment = 'dev'; //dev, staging or live
    
    require_once 'CSV.class.php';

function logg($message)
{
	echo date('Y-m-d H:i:s') . ' - ' . $message . PHP_EOL;
}

switch ($environment) {
case 'live':
	$host = 'entdb.aws';
	$db = new mysqli($host, 'sykes', 'foo32PANTS', 'whitelabels');
	break;

case 'staging':
	$host = 'staging-enterprise.c9pgsirh8via.eu-west-1.rds.amazonaws.com';
	$db = new mysqli($host, 'sykes', 'foo32PANTS', 'whitelabels');
	break;

default:
	$host = 'thordevdb.office';
    $db = new mysqli($host, 'marcin.hernik', 'ca0bd5db974111a851335f466e8ca1ee', 'whitelabels');
}

$db->set_charset('utf8');

$redirects = CSV::readCSVintoArray('hideaways301.csv');
array_shift($redirects); //Bin off the headers

//get existing redirects
$existing_url_results = $db->query("
    SELECT url FROM redirects WHERE brand_id = $brand;
");

$existing_redirects = [];

foreach($existing_url_results as $existing_url_result) {
    array_push($existing_redirects, $existing_url_result['url']);
}

foreach($redirects as $redirect) {
    
    //bin off parts of url
    $redirect[0] = str_replace('http://www.hideaways.co.uk', '', $redirect[0]);
    $redirect[1] = str_replace('https://www.hideaways.co.uk', '', $redirect[1]);
    
    if(substr($redirect[0], -5) == '.html' && !in_array($redirect[0], $existing_redirects)) {
        $query = $db->query("
                    INSERT INTO redirects (url, redirect, brand_id, code) VALUES ('$redirect[0]', '$redirect[1]', $brand, 301);
                ");
        if($db->error) {
            logg($db->error);
        } else {
            echo("Created redirect from ".$redirect[0]." to ".$redirect[1]." from 1".PHP_EOL);
            array_push($existing_redirects, $redirect[0]);
        }
    }
    
    if(substr($redirect[0], -1) == '/' && !in_array($redirect[0], $existing_redirects)) {
        $redirect_with_trailing_slash = $redirect[0];
        $query = $db->query("
                    INSERT INTO redirects (url, redirect, brand_id, code) VALUES ('$redirect_with_trailing_slash', '$redirect[1]', $brand, 301);
                ");
        if($db->error) {
            logg($db->error);
        } else {
            echo("Created redirect from ".$redirect[0]." to ".$redirect[1]." from 2".PHP_EOL);
            array_push($existing_redirects, $redirect_with_trailing_slash);
        }
        if(!in_array(substr_replace($redirect_with_trailing_slash, '', -1), $existing_redirects)) {
            $redirect_with_removed_trailing_slash = substr_replace($redirect_with_trailing_slash, '', -1);
            $query = $db->query("
                    INSERT INTO redirects (url, redirect, brand_id, code) VALUES ('$redirect_with_removed_trailing_slash', '$redirect[1]', $brand, 301);
                ");
            if($db->error) {
                logg($db->error);
            } else {
                echo("Created redirect from ".$redirect_with_removed_trailing_slash." to ".$redirect[1]." from 3".PHP_EOL);
                array_push($existing_redirects, $redirect_with_removed_trailing_slash);
            }
        }
    }
    
    if(substr($redirect[0], -1) != '/' && substr($redirect[0], -5) != '.html' && !in_array($redirect[0], $existing_redirects)) {
        $redirect_without_trailing_slash = $redirect[0];
        $query = $db->query("
                    INSERT INTO redirects (url, redirect, brand_id, code) VALUES ('$redirect_without_trailing_slash', '$redirect[1]', $brand, 301);
                ");
        if($db->error) {
            logg($db->error);
        } else {
            echo("Created redirect from ".$redirect[0]." to ".$redirect[1]." from 4".PHP_EOL);
            array_push($existing_redirects, $redirect_without_trailing_slash);
        }
        if(!in_array($redirect_without_trailing_slash.'/', $existing_redirects)) {
            $redirect_with_added_trailing_slash = $redirect_without_trailing_slash.'/';
            $query = $db->query("
                    INSERT INTO redirects (url, redirect, brand_id, code) VALUES ('$redirect_with_added_trailing_slash', '$redirect[1]', $brand, 301);
                ");
            if($db->error) {
                logg($db->error);
            } else {
                echo("Created redirect from ".$redirect_with_added_trailing_slash." to ".$redirect[1]." from 5".PHP_EOL);
                array_push($existing_redirects, $redirect_with_added_trailing_slash);
            }
        }
    }
}

$new_urls_to_check = $db->query("
    SELECT url, redirect FROM redirects WHERE brand_id = $brand;
");

$new_url_results = [];

foreach($new_urls_to_check as $new_url_to_check) {
    array_push($new_url_results, $new_url_to_check);
}

$new_results_to_check = [];

foreach($new_url_results as $new_url_result) {
    array_push($new_results_to_check, $new_url_result['url']);
}




foreach($new_url_results as $new_url_result) {
    $url = $new_url_result['url'];
    $redirect = $new_url_result['redirect'];
    
    if(substr($url, -1) == '/' && !in_array(substr_replace($url, '', -1), $new_results_to_check)) {
        $new_url_with_trimmed_trailing_slash = substr_replace($url, '', -1);
        $query = $db->query("
                    INSERT INTO redirects (url, redirect, brand_id, code) VALUES ('$new_url_with_trimmed_trailing_slash', '$redirect', $brand, 301);
                ");
        if($db->error) {
            logg($db->error);
        } else {
            echo("Created redirect from ".$new_url_with_trimmed_trailing_slash." to ".$redirect." from 6".PHP_EOL);
        }
    }
    
    if(substr($url, -1) != '/' && substr($url, -5) != '.html' && !in_array($url.'/', $new_results_to_check)) {
        $new_url_with_added_trailing_slash = $url.'/';
        $query = $db->query("
                    INSERT INTO redirects (url, redirect, brand_id, code) VALUES ('$new_url_with_added_trailing_slash', '$redirect', $brand, 301);
                ");
        if($db->error) {
            logg($db->error);
        } else {
            echo("Created redirect from ".$new_url_with_added_trailing_slash." to ".$redirect." from 7".PHP_EOL);
        }
    }
    
}

