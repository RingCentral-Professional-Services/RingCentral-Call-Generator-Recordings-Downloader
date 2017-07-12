<?php

use RingCentral\SDK\Http\HttpException;
use RingCentral\http\Response;
use RingCentral\SDK\SDK;


require('./vendor/autoload.php');
require('./modules/util.php');
require('./modules/rc_api.php');
require('./modules/extension.php');
require('./modules/calllog.php');

date_default_timezone_set ('UTC');


// To parse the .env file
$dotenv = new Dotenv\Dotenv(getcwd());

$dotenv->load();

#require('./modules/_bootstrap.php');

$rcsdk = new SDK($_ENV['RC_AppKey'], $_ENV['RC_AppSecret'], $_ENV['RC_Server'], 'App', '1.0');
$platform = $rcsdk->platform();

require('./modules/init.php');
require('./modules/save_recording_s3.php');
#rcLog($global_logFile, 1, 'Start to retrieve call logs from RingCentral PAS.');
require('./modules/auth.php');
$global_extensions=getAllExtensions($platform);
echo "Total number of extensions ".count($global_extensions).".\n";
$global_phoneNumbers=getAllPhoneNumbers($platform);
echo "Total number of phone numbers ".count($global_phoneNumbers).".\n";

iterateCallLogs($platform, $dateFromTime, $dateToTime, function($page) use($global_phoneNumbers, $global_extensions, $platform) {
	echo "Get call log page {$page->paging->page}.\n";
	$callLogs=$page->records;
	$count=count($callLogs);
	for($i=1; $i<=$count; $i++) {
		echo "Processing call log $i/$count.\r";
		$callLog=$callLogs[$i-1];
		populateCallLogOwner($callLog, $global_phoneNumbers, $global_extensions);
		parseCallLogDate($callLog, $platform);
		$recording=parseCallRecording($callLog);
		saveRecordingS3($recording, $platform);
		echo "Call recording {$recording['filePath']} saved to S3.\n";
	}
	echo "\n";
});


$global_appData['lastRunningTime'] = $global_currentTime;
file_put_contents($global_appDataFile, json_encode($global_appData, JSON_PRETTY_PRINT));

