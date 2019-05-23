<?php
$brand = 316; // 7 for cornish, 8 for devonshire 9 for lakes 10 for helpful 311 for manor, 312 for Haddaway
$file = "ccc_landingpages.csv";
$environment = 'dev'; //dev, staging or live

function logg($message)
{
	echo date('Y-m-d H:i:s') . ' - ' . $message . PHP_EOL;
}

switch ($environment) {
// case 'live':
// 	$host = 'entdb.aws';
// 	$db = new mysqli($host, 'sykes', 'foo32PANTS', 'whitelabels');
// 	break;
//
// case 'staging':
// 	$host = 'staging-enterprise.c9pgsirh8via.eu-west-1.rds.amazonaws.com';
// 	$db = new mysqli($host, 'sykes', 'foo32PANTS', 'whitelabels');
// 	break;

default:
	$host = 'thordevdb.office';
	$db = new mysqli($host, 'marcin.hernik', 'ca0bd5db974111a851335f466e8ca1ee', 'whitelabels');
}

$db->set_charset('utf8');
$handle = fopen($file, 'r');
$counter = 0;

/*
$query = $db->query("
    SELECT page_id FROM tbl_page_rewrites WHERE brand_id = $brand
");

//full removal of brand pages pointing to this brand
$rows = $query->fetch_all();
foreach($rows as $row) {
    if($row[0] !== 8476) {
        $search_query = $db->query("
        SELECT * FROM tbl_searches_page WHERE page_id = $row[0];
    ");
        $search_id = $search_query->fetch_all();
        if(!empty($search_id)) {
            $db->query("
        DELETE FROM tbl_searches_page WHERE page_id = $row[0];
        ");
            echo("Removed search for page $row[0]").PHP_EOL;
        } else {
            echo("No search found for page $row[0]").PHP_EOL;
        }
        var_dump("
        DELETE FROM tbl_page_content WHERE page_id = $row[0];
        DELETE FROM tbl_page_content WHERE page_id = $row[0];
        DELETE FROM tbl_page_rewrites WHERE page_id = $row[0];
        DELETE FROM tbl_pages WHERE page_id = $row[0];
    ");
        $sql = "
        DELETE FROM tbl_page_content WHERE page_id = $row[0];
        DELETE FROM tbl_page_content WHERE page_id = $row[0];
        DELETE FROM tbl_page_rewrites WHERE page_id = $row[0];
        DELETE FROM tbl_pages WHERE page_id = $row[0];
    ";
        $removalQuery = $db->query($sql);
        if($removalQuery === TRUE) {
            echo("Removed page $row[0]").PHP_EOL;
        } else {
            echo("There were errors removing page $row[0]").PHP_EOL;
        }
    }
}
*/

//safer option two - removes only rewrite for all pages for the brand
/*
$db->query("
    DELETE FROM tbl_page_rewrites WHERE brand_id = $brand;
");
*/
while ($row = fgetcsv($handle)) {
	if ($counter++ == 0) {
		continue;
	}

	// insert page

	if (trim($row[1]) == "") {
		continue;
	}

	$db->query("
        INSERT INTO tbl_pages (page_title, browser_title, description, publish_date, live_date, enabled)
        VALUES('$row[4]', '$row[2]', '$row[3]', '2017-01-01 00:00:00', '2017-01-01 00:00:00', 1)
    ");
	if ($db->error) {
		logg('tbl_pages - ' . $db->error);
		continue;
	}

	$page_id = $db->insert_id;

	// insert redirect

	if ($row[1][0] != '/') {
		$row[1] = '/' . $row[1];
	}

	$db->query("
        INSERT INTO tbl_page_rewrites (page_id, rule, brand_id)
        VALUES ($page_id, '$row[1]', $brand)
    ");
	if ($db->error) {
		logg('tbl_page_rewrites - ' . $db->error);
		continue;
	}

	$row[5] = $db->real_escape_string($row[5]);
	$c = "";
	if ($row[5] != "") {

		// insert content

		$db->query("
        INSERT INTO tbl_page_content (page_id, content)
        VALUES ($page_id, '$row[5]')
    ");
		if ($db->error) {
			logg('tbl_page_content - ' . $db->error);
			continue;
		}

		$c = "C";
	}

	$fields = [];
	$values = [];
	foreach([6 => 'location',
	        7 => 'locationdist',
	        8 => 'pets',
	        9 => 'hot_tub',
	        10 => 'romantic',
	        11 => 'luxury',
	        12 => 'ncoastal',
	        13 => 'groundfloor',
	        14 => 'num_sleeps',
	        15 => 'new',
	        16 => 'start_date',
	        17 => 'end_date',
	        18 => 'logcabin',
	        19 => 'near_pub',
	        20 => 'garden',
	        21 => 'lspecial_offers',
	        22 => 'swimming_pool',
	        23 => 'area_id',
            24 => 'childfriendly',
            25 => 'farm',
            26 => 'ecofriendly',
            27 => 'lastminutebreakallowed',
            28 => 'country_id',
            29 => 'region_id',
            30 => 'shortbreaksallowed'
	         ] as $index => $field) {
		if ($row[$index] || is_numeric($row[$index])) {
			$fields[] = $field;
			$values[] = $row[$index];
		}
	}

	$searchFound = false;
	foreach($values as $value) {
		if ($value != "") {
			$searchFound = true;
		}
	}

	if (!$searchFound) {
		logg('PW' . $c . ' for ' . $row[1]);
		continue;
	}

	if (!in_array("locationdist", $fields)) {
		$fields[] = "locationdist";
		$values[] = 10;
	}

	$fields = implode(', ', $fields);
	$values = "'" . implode("', '", $values) . "'";
	$db->query("
        INSERT INTO tbl_searches ($fields)
        VALUES ($values)
    ");
	if ($db->error) {
		logg('tbl_searches - ' . $db->error);
		continue;
	}

	$search_id = $db->insert_id;

	// insert search to page
	$db->query("
        INSERT INTO tbl_searches_page (search_id, page_id)
        VALUES ($search_id, $page_id)
    ");
	if ($db->error) {
		logg('tbl_searches_page - ' . $db->error);
	}

	logg('PW' . $c . 'S for ' . $row[1]);
}
