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
	$db = new mysqli($host, 'root', 'foo32PANTS', 'whitelabels');
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

//$redirects = CSV::readCSVintoArray('redirectscch.csv');
//array_shift($redirects); //Bin off the headers

//get existing content
$existing_pages = $db->query("
    SELECT tbl_page_rewrites.page_id, tbl_page_content.content FROM tbl_page_rewrites JOIN tbl_page_content
ON tbl_page_rewrites.page_id=tbl_page_content.page_id WHERE tbl_page_rewrites.brand_id = 7
");

$x = 0;

$re = '/href=[\"\'](https?:\/\/www.cornishcottageholidays.co.uk)?\/([A-Za-z0-9\-]*\/?)*[\"\']/m';
$existing_links = [];
$missing_links = [];
$covered_links = [];
$existing_old_pages = [];

foreach($existing_pages as $existing_page) {
    /*
    preg_match_all($re, $existing_page['content'], $matches, PREG_SET_ORDER, 0);
    foreach($matches as $match) {
        print_r($matches[0][0].PHP_EOL);
        $match_to_write = str_replace("href=", '', $matches[0][0]);
        $match_to_write = str_replace('"', '', $match_to_write);
        $existing_links[] = $match_to_write;
    }
    */
    
    var_dump($existing_page['content']);
    
    if(strpos($existing_page['content'], '<img src=') !== false) {
         $existing_page['content'] = preg_replace('\'<img(.*?)>(.*?)/>\'', '', $existing_page['content']);
    }
    
    /*
    if(strpos($existing_page['content'], '/mailto:')) {
        $replaced_content = str_replace('/mailto:', 'mailto:', $existing_page['content']);
        $page_id = $existing_page['page_id'];
        $query = $db->query("
                    UPDATE tbl_page_content SET content = '$replaced_content' WHERE page_id = $page_id;
                ");
        if($db->error) {
            logg($db->error);
        } else {
            echo("Updated $page_id".PHP_EOL);
        }
    }
    */
    
}
/*
$old_urls = CSV::readCSVintoArray('cchContent.csv');

foreach($old_urls as $old_url) {
    var_dump($old_url[0]);
    $existing_old_pages[] = "http://www.cornighcottageholidays.co.uk".$old_url[0];
}

foreach($existing_links as $existing_link) {
    if(in_array($existing_link, $existing_old_pages)) {
        $covered_links[] = $existing_link;
    } else {
        $missing_links[] = $existing_link;
    }
}

var_dump($missing_links);
var_dump($covered_links);
*/
