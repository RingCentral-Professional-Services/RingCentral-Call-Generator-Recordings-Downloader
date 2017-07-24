<?php

function iterateCallLogs($platform, $dateFromTime, $dateToTime, callable $cb) {
        return rcIterateAllPages($platform, '/account/~/call-log', array(
            #'withRecording' => 'True',
            'dateFrom' => date('Y-m-d\TH:i:s\Z', $dateFromTime),	// test value: "2017-07-06T18:57:00.000Z"
            'dateTo' => date('Y-m-d\TH:i:s\Z', $dateToTime), // test value: "2017-07-06T19:00:00.000Z"
            'type' => 'Voice',
            'perPage' => 1000,
            'view' => 'Detailed'
        ), $cb);
}


function parseCallRecording($callLog) {
	$paths = array();
	if(isset($callLog->extensions)) { # Internal calls, belongs to both to and from, should store recording for from and to
		$twoSides = splitInternalCall($callLog);
		array_push($paths, getRecordingFilePath($twoSides['from']), getRecordingFilePath($twoSides['to']));
	} else {
		array_push($paths, getRecordingFilePath($callLog));
	}

	return array(
		'recordingId' => $callLog->recording->id,
		'filePaths' => $paths,
		'recordingUrl' => $callLog->recording->contentUri
	);
}

function splitInternalCall($callLog) {
	$fromLog = clone $callLog;
	unset($fromLog->extensions);
	$fromLog->direction = 'Outbound';
	$fromLog->extension = $callLog->extensions['from'];

	$toLog = clone $callLog;
	unset($toLog->extensions);
	$toLog->direction = 'Inbound';
	$toLog->extension = $callLog->extensions['to'];
	return array('from'=>$fromLog, 'to'=>$toLog);
}

function getRecordingFilePath($callLog) {
        $otherNumber="blocked";
        $indicator=null;

        if($callLog->direction=="Inbound") {
                $indicator="from";
                if(isset($callLog->from)) {
                        $otherNumber=getCallerNumber($callLog->from);
                }
        } else {
                $indicator="to";
                $otherNumber=getCallerNumber($callLog->to);
        }
       $callStartTime=$callLog->startTime;
        return getCallLogFolder($callLog)."{$indicator}_{$otherNumber}_{$callStartTime->format('H:i:s')}_{$callLog->recording->id}_{$callLog->id}";
}

function getCallLogFolder($callLog) {
	if(isset($callLog->extension->name)) {
		$owner = $callLog->extension->name;
	} else {
		$ownerCaller = $callLog->direction == 'Inbound' ? $callLog->to : $callLog->from;
		if(isset($ownerCaller->name)) {
			$owner = $ownerCallder->name;
		} else {
			$owner = getCallerNumber($ownerCaller);
		}
	}
	return "$owner/{$callLog->startTime->format('Y-m-d')}/";
}

function logNoRecording($callLog, &$logs) {
	if(isset($callLog->extensions)) {
		$twoSides = splitInternalCall($callLog);
		logNoRecording($twoSides['from'], $logs);
		logNoRecording($twoSides['to'], $logs);
		return;
	}
	if(isset($callLog->from)) {
		$fromNumber = getCallerNumber($callLog->from);
	} else {
		$fromNumber = '';
	}
	if(isset($callLog->extension->name)) {
		$ownerName = $callLog->extension->name;
	} else {
		$ownerName = '';
	}
	$csvRecord = array($callLog->direction, $ownerName, $fromNumber, getCallerNumber($callLog->to), $callLog->startTime->format('Y-m-d H:i:s'), $callLog->id);
	$path = getCallLogFolder($callLog)."calls-no-recordings.csv";
	if(!isset($logs[$path])) {
		$logs[$path]=array($csvRecord);
	} else {
		array_push($logs[$path], $csvRecord);
	}
}

function getCallerNumber($caller) {
	if(isset($caller->phoneNumber)) {
		return $caller->phoneNumber;
	} else if(isset($caller->extensionNumber)) {
		return $caller->extensionNumber;
	}
}
