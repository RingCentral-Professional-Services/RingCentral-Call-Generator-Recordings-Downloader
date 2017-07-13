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
require('./modules/auth.php');
echo "Getting all extensions: ";
$global_extensions=getAllExtensions($platform);
echo count($global_extensions)." got.\n";

echo "Getting all phone numbers: ";
$global_phoneNumbers=getAllPhoneNumbers($platform);
echo count($global_phoneNumbers)." got.\n";

echo "Loading call log from ".date('Y-m-d H:i:s', $dateFromTime)." to ".date('Y-m-d H:i:s', $dateToTime).".\n";
iterateCallLogs($platform, $dateFromTime, $dateToTime, function($page) use($global_phoneNumbers, $global_extensions, $platform) {
	echo "Get call log page {$page->paging->page}.\n";
	$callLogs=$page->records;
	$count=count($callLogs);
	$startTime=time();
	for($i=1; $i<=$count; $i++) {
		echo "Processing call log $i/$count.\r";
		$callLog=$callLogs[$i-1];
		populateCallLogOwner($callLog, $global_phoneNumbers, $global_extensions);
		parseCallLogDate($callLog, $platform);
		$recording=parseCallRecording($callLog);
		saveRecordingS3($recording, $platform);
		echo "Call recording [{$recording['filePath']}] saved to S3.\n";
		$timeElapsed=time()-$startTime;
		$speed=round($timeElapsed/$i, 2);
		echo "                                 Speed: $speed s/item, Time elapsed: {$timeElapsed}s.\r";
	}
	echo "\n";
});
echo "All call logs processed.\n";


$global_appData['lastRunningTime'] = $global_currentTime;
file_put_contents($global_appDataFile, json_encode($global_appData, JSON_PRETTY_PRINT));

