<?php

function iterateCallLogs($platform, $dateFromTime, $dateToTime, callable $cb) {
        return rcIterateAllPages($platform, '/account/~/call-log', array(
            'withRecording' => 'True',
            'dateFrom' => date('Y-m-d\TH:i:s\Z', $dateFromTime),	// test value: "2017-07-06T18:57:00.000Z"
            'dateTo' => date('Y-m-d\TH:i:s\Z', $dateToTime), // test value: "2017-07-06T19:00:00.000Z"
            'type' => 'Voice',
            'perPage' => 1000,
            'view' => 'Detailed'
        ), $cb);
}


function parseCallRecording($callLog) {
	$number=null;
	$otherNumber="blocked";
	$callLogOwner=null;
	$indicator=null;

	if($callLog->direction=="Inbound") {
		$indicator="from";
		$number=getCallerNumber($callLog->to);
		if(isset($callLog->from)) {
			$otherNumber=getCallerNumber($callLog->from);
		}
	} else {
		$indicator="to";
		$number=getCallerNumber($callLog->from);
		$otherNumber=getCallerNumber($callLog->to);
	}
	if(isset($callLog->extension)) {
		$callLogOwner=$callLog->extension->name;
	} else {
		$callLogOwner=$number;
	}
	$callStartTime=$callLog->startTime;
	$filePath="$callLogOwner/{$callStartTime->format('Y-m-d')}/{$indicator}_{$otherNumber}_{$callStartTime->format('H:i:s')}_{$callLog->recording->id}_{$callLog->id}";
	return array(
		'recordingId' => $callLog->recording->id,
		'filePath' => $filePath,
		'recordingUrl' => $callLog->recording->contentUri
	);
}

function getCallerNumber($caller) {
	if(isset($caller->phoneNumber)) {
		return $caller->phoneNumber;
	} else if(isset($caller->extensionNumber)) {
		return $caller->extensionNumber;
	}
}
