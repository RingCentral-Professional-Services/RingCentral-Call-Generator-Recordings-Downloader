<?php
ini_set('memory_limit', '256M');
$global_cacheDir = './_cache';
$global_appDataFile = $global_cacheDir . '/app_data.json';
$global_logFile = $global_cacheDir.'/log';
$global_currentTime = time();
$global_timeOffset = $_ENV['Time_Offset'];

$global_accountExtensions = null;
$global_phoneNumbers = null;
$global_callLogs = null;

if (!file_exists($global_cacheDir)) {
    mkdir($global_cacheDir);
}

$global_appData = array(
    'lastRunningTime' => null
);

if (file_exists($global_appDataFile)) {
    $global_appData = json_decode(file_get_contents($global_appDataFile), true);
}

   $maxRetrieveTimeSpan = $_ENV['RC_maxRetrieveTimespan'];

    $dateFromTime = $global_currentTime - $global_timeOffset - $maxRetrieveTimeSpan;
    $dateToTime = $global_currentTime - $global_timeOffset;

    if(isset($global_appData['lastRunningTime'])){
        $dateFromTime = $global_appData['lastRunningTime'] - $global_timeOffset + 1;
    }






