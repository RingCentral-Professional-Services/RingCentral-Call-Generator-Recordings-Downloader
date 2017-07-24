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
$noRecordingCalls = array();	# file path => array of csv lines

require('./modules/init.php');
require('./modules/save_recording_s3.php');
require('./modules/auth.php');
echo "Getting all extensions: ";
$global_extensions=getAllExtensions($platform);
echo count($global_extensions)." got.\n";

echo "Getting all phone numbers: ";
$global_phoneNumbers=getAllPhoneNumbers($platform);
echo count($global_phoneNumbers)." got.\n";

echo "Loading call log from ".date('Y-m-d H:i:s', $dateFromTime)." to ".date('Y-m-d H:i:s', $dateToTime).".\n";
iterateCallLogs($platform, $dateFromTime, $dateToTime, function($page) use(&$noRecordingCalls, $global_phoneNumbers, $global_extensions, $platform) {
	echo "Get call log page {$page->paging->page}.\n";
	$callLogs=$page->records;
	$count=count($callLogs);
	$startTime=time();
	for($i=1; $i<=$count; $i++) {
		echo "Processing call log $i/$count.\r";
		$callLog=$callLogs[$i-1];
		populateCallLogOwner($callLog, $global_phoneNumbers, $global_extensions);
		parseCallLogDate($callLog, $platform);
		if(isset($callLog->recording)) {
			$recording=parseCallRecording($callLog);
			saveRecordingS3($recording, $platform);
			$paths = implode(", ", $recording['filePaths']);
			echo "Call recording [$paths] saved to S3.\n";
		}else {
			logNoRecording($callLog, $noRecordingCalls);
			echo "No recording for call log {$callLog->id}.\n";
		}
		$timeElapsed=time()-$startTime;
		$speed=round($i*60/$timeElapsed, 2);
		echo "                                 Speed: $speed logs/min, Time elapsed: {$timeElapsed}s.\r";
	}
	echo "\n";
});

saveNoRecordingCalls($noRecordingCalls);

echo "All call logs processed.\n";


$global_appData['lastRunningTime'] = $global_currentTime;
file_put_contents($global_appDataFile, json_encode($global_appData, JSON_PRETTY_PRINT));

