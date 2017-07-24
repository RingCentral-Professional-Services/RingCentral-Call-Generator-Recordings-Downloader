<?php

use Aws\S3\S3Client;
use Aws\Common\Aws;
use Aws\Ses\SesClient;

$awsS3Client = S3Client::factory(array(
    'key' => $_ENV['amazonAccessKey'],
    'secret' => $_ENV['amazonSecretKey'],
    'region' => $_ENV['amazonRegion'],
    'command.params' => ['PathStyle' => true]
));
    
// Register the stream wrapper from an S3Client object
$awsS3Client->registerStreamWrapper();


function saveRecordingS3($recording, $platform) {
	$file=retrieveRecording($platform, $recording['recordingUrl']);

	foreach($recording['filePaths'] as $path) {
		$s3FileName = "s3://".$_ENV['amazonS3Bucket'].'/'.$path.'.'.$file['ext'];
		// Write the file to S3 Bucket
		file_put_contents($s3FileName, $file['data']);
	}
}

function retrieveRecording($platform, $uri) {
	$apiResponse = rcApiGet($platform, $uri, null);
	return array(
		'ext' => ($apiResponse->response()->getHeader('Content-Type')[0] == 'audio/mpeg') ? 'mp3' : 'wav',
    		'data' => $apiResponse->raw()
	);
}

function saveNoRecordingCalls($logs) {
	foreach($logs as $path => $csvRecords) {
		$s3FileName = "s3://".$_ENV['amazonS3Bucket'].'/'.$path;
		$existed = file_exists($s3FileName);
		$s3fd = fopen($s3FileName, 'a');
		if(!$existed) {
			echo "Creating csv file $path.\n";
			fputcsv($s3fd, array('Direction', 'Extension Name', 'From Number', 'To Number', 'Date/Time', 'Call Record ID'));
		}
		echo "Writing to csv file $path.\n";
		foreach($csvRecords as $csvRecord) {
			fputcsv($s3fd, $csvRecord);
		}
		fclose($s3fd);
	}
}
