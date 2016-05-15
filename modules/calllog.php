<?php

try {
    
    $maxRetrieveTimeSpan = $_ENV['RC_maxRetrieveTimespan'];

    $currentTime = time();
    $dateFromTime = $currentTime - $maxRetrieveTimeSpan;
    $dateToTime = $currentTime;
    
    if(isset($global_appData['lastRunningTime'])){
        if($currentTime - $global_appData['lastRunningTime'] <= $maxRetrieveTimeSpan) {
            $dateFromTime = $global_appData['lastRunningTime'] + 1;
        }
    }

    $global_callLogs = requestMultiPages($platform, '/account/~/call-log', array(
        'withRecording' => 'True',
        'dateFrom' => date('Y-m-d\TH:i:s\Z', $dateFromTime),
        'dateTo' => date('Y-m-d\TH:i:s\Z', $dateToTime),
        'type' => 'Voice',
        'perPage' => 500,
        'page' => 1
    ));
    
    $global_appData['lastRunningTime'] = $currentTime;
    
    file_put_contents($global_appDataFile, json_encode($global_appData, JSON_PRETTY_PRINT));
    
    if(count($global_callLogs) > 0) {
        rcLog($global_logFile, 0, 'Call Logs Loaded!');
        foreach ($global_callLogs as $callLog) {
            rcLog($global_logFile, 0, $callLog->uri);
        }
    }
    
} catch (Exception $e) {
    rcLog($global_logFile, 1, 'Error occurs when retrieving call logs -> ' . $e->getMessage());
    throw $e;    
}	


