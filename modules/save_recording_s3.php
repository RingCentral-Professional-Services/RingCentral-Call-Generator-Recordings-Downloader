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

	$s3FileName = "s3://".$_ENV['amazonS3Bucket'].'/'.$recording['filePath'].'.'.$file['ext'];
	// Write the file to S3 Bucket
	file_put_contents($s3FileName, $file['data']);
}

function retrieveRecording($platform, $uri) {
	$apiResponse = rcApiGet($platform, $uri, null);
	return array(
		'ext' => ($apiResponse->response()->getHeader('Content-Type')[0] == 'audio/mpeg') ? 'mp3' : 'wav',
    		'data' => $apiResponse->raw()
	);
}
