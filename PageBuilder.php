<?php
// Landing Page Builder Script



// Very Important that the next 5 variables are correctly set
// You will need to find the value of the next auto-increment on whitelabels.tbl_pages and whitelabels.tbl_searches

$brand = 317; // currently set for coastandcountry
$page_id = 12201;  // Find the last page_id written in the DB and add 1
$search_id = 86699200; // Find the last search_id written in the DB and add 1
$first_page_id = 12201;  // Record first page id for report at end of script
$first_search_id = 86699200;  // Record first page id for report at end of script
$file = "dreams_landing_pages.csv";
$sqls = $brand . "_pages.txt";

$tbl_page_sql="";
$tbl_page_rewrites_sql="";
$tbl_page_content_sql="";
$tbl_searches_sql="";
$tbl_searches_page_sql="";

$this_execute_start = '$this->execute("';
$this_execute_stop = '");';

$read_handle = fopen($file, 'r');
$write_handle = fopen($sqls, 'w');

// Open a DB connection to make use of the real_escape_string function
$host = 'thordevdb.office';
$db = new mysqli($host, 'andy.holderness', '4s1u2O38cLZCZYhbx51Ave7Ku285OjJd', 'whitelabels');

$db->set_charset('utf8');

while ($row = fgetcsv($read_handle)) {
    if ($counter++ == 0) {
        continue;
    }

    // insert page
    if (trim($row[1]) == "") {
        continue;
    }

    $tbl_page_sql = $this_execute_start . "INSERT INTO `whitelabels`.`tbl_pages` (page_id, page_title, browser_title, description, publish_date, live_date, enabled) VALUES($page_id, '$row[4]', '$row[2]', '$row[3]', '2018-08-01 00:00:00', '2018-08-01 00:00:00', 1)" . $this_execute_stop;

    // insert redirect
    if ($row[1][0] != '/') {    $row[1] = '/' . $row[1];    }

    $tbl_page_rewrites_sql = $this_execute_start . "INSERT INTO `whitelabels`.`tbl_page_rewrites` (page_id, rule, brand_id) VALUES ($page_id, '$row[1]', $brand)" . $this_execute_stop;

    $row[5] = $db->real_escape_string($row[5]);
    if ($row[5] != "") {

    // insert content
    $tbl_page_content_sql = $this_execute_start . "INSERT INTO `whitelabels`.`tbl_page_content` (page_id, content) VALUES ($page_id, '$row[5]')" . $this_execute_stop;
    }

    // links the search just created above to the page header, content and rewrite
    $tbl_searches_page_sql = $this_execute_start . "INSERT INTO `whitelabels`.`tbl_searches_page` (search_id, page_id) VALUES ($search_id, $page_id)" . $this_execute_stop;

// Search records
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
                26 => 'family',
                27 => 'lastminutebreakallowed',
                28 => 'country_id',
                29 => 'region_id',
                30 => 'area_id',
                31 => 'shortbreaksallowed',
                32 => 'open_fire',
                33 => 'nearriver'

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

    if (!in_array("locationdist", $fields)) {
        $fields[] = "locationdist";
        $values[] = 10;
    }

    $fields = implode(', ', $fields);
    $values = "'" . implode("', '", $values) . "'";

    $tbl_searches_sql = $this_execute_start . "INSERT INTO `whitelabels`.`tbl_searches` (search_id, $fields) VALUES ($search_id, $values) " . $this_execute_stop;

    // write the sql lines into the file
    fwrite($write_handle, $tbl_page_rewrites_sql . "\n");
    fwrite($write_handle, $tbl_page_sql . "\n");
    fwrite($write_handle, $tbl_page_content_sql . "\n");
    fwrite($write_handle, $tbl_searches_page_sql . "\n");
    fwrite($write_handle, $tbl_searches_sql . "\n");

    echo($tbl_page_sql . "\n");
    echo($tbl_page_rewrites_sql . "\n");
    echo($tbl_page_content_sql . "\n");
    echo($tbl_searches_sql . "\n");
    echo($tbl_searches_page_sql . "\n");

    $page_id++;
    $search_id++;
}

$number_pages_added = $page_id - $first_page_id;
$number_searches_added = $search_id - $first_search_id;

echo $number_pages_added . " pages ready for creation \n";
echo $number_searches_added . " searches ready for creation \n";