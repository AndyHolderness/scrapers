<?php


$brand = 311; 
$environment = 'dev'; //dev, staging or live

require_once 'CSV.class.php';

    echo date('Y-m-d H:i:s') . ' - ' . $message . PHP_EOL;


switch ($environment) {
    case 'staging':
        $host = 'staging-enterprise.c9pgsirh8via.eu-west-1.rds.amazonaws.com';
        $db = new mysqli($host, 'sykes', 'foo32PANTS', 'whitelabels');
        break;

    default:
        $host = 'thordevdb.office';
        $db = new mysqli($host, 'marcin.hernik', 'ca0bd5db974111a851335f466e8ca1ee', 'whitelabels');
}

$db->set_charset('utf8');







