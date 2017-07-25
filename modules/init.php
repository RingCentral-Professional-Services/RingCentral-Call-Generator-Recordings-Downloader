<?php
ini_set('memory_limit', '256M');
date_default_timezone_set ('UTC');
// To parse the .env file
$dotenv = new Dotenv\Dotenv(getcwd());
$dotenv->load();

$global_cacheDir = './_cache';
$global_appDataFile = $global_cacheDir . '/app_data.json';
$global_logFile = $global_cacheDir.'/log';
$global_timeOffset = $_ENV['Time_Offset'];
$maxRetrieveTimeSpan = $_ENV['RC_maxRetrieveTimespan'];

if (!file_exists($global_cacheDir)) {
    mkdir($global_cacheDir);
}

$global_appData = array(
    'lastRunningTime' => null
);
if (file_exists($global_appDataFile)) {
    $global_appData = json_decode(file_get_contents($global_appDataFile), true);
}






