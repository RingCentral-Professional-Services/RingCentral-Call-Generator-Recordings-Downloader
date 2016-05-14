<?php

use RingCentral\SDK\Http\HttpException;
use RingCentral\http\Response;
use RingCentral\SDK\SDK;


require('./vendor/autoload.php');

date_default_timezone_set ('UTC');


// To parse the .env file
$dotenv = new Dotenv\Dotenv(getcwd());

$dotenv->load();

require('./modules/_bootstrap.php');

$rcsdk = new SDK($_ENV['RC_AppKey'], $_ENV['RC_AppSecret'], $_ENV['RC_Server'], 'App', '1.0');
$platform = $rcsdk->platform();


require(__DIR__ . '/modules/util.php');

rcLog($logFile, 0, 'Application Start');

require(__DIR__ . '/modules/auth.php');
require(__DIR__ . '/modules/extension.php');
require(__DIR__ . '/modules/calllog.php');

rcLog($logFile, 0, 'Start to retrieve recordings!');

foreach ($callLogs as $callLog) {
    try{
        $recording = retrieveRecording($platform, $callLog);
        $filePaths = array();
        require('./modules/file_struct_s3.php');
        require('./modules/save_recording_s3.php');
    }catch(Exception $e) {
        rcLog($logFile, 1, 'Error occurs when sending recording '.$callLog->recording->id.' -> ' . $e->getMessage());
    }
}

