<?php
$brand = 311; 

	$host = 'thordevdb.office';
    $db = new mysqli($host, 'andy.holderness', '4s1u2O38cLZCZYhbx51Ave7Ku285OjJd', 'whitelabels');

$db->set_charset('utf8');

$pages = $db->query("
    SELECT page_id FROM tbl_page_rewrites WHERE brand_id = $brand;
");



foreach($pages as $page) {
    var_dump($page['page_id']);
    
    $query = $db->query("
        SELECT * FROM tbl_searches_page WHERE page_id = ".$page['page_id'].";"
    );

    if(mysqli_num_rows($query)) {
        foreach($query as $quer) {
            array_push($landing_pages, $quer['page_id']);
        }
    } else {
        array_push($static_pages, $page['page_id']);
    }
    
    
}

foreach($static_pages as $static_page) {
    $query = $db->query("
    
    echo "Set page $static_page as static page";
}

foreach($landing_pages as $landing_page) {
    $query = $db->query("
        UPDATE tbl_pages SET set_id = 32, parent_id = 9220 WHERE page_id = $landing_page;
    ");
    if($db->error) {
        logg($db->error);
    }
    echo "Set page $landing_page as landing page";
}



