<?php

// Add owner extension info for each call log item
function populateCallLogOwner($callLog, $phoneNumbers, $extensions) {
	$owner = getCallLogOwner($callLog, $extensions, $phoneNumbers);
	if(isInternalCall($callLog)) {
		$callLog->extensions = $owner;
	} else {
		$callLog->extension = $owner;
	}
}

// Get the owner extension for the account call log item
function getCallLogOwner($callLog, $extensions, $phoneNumbers) {
	if(isset($callLog->from))
		$from = $callLog->from;
	$to = $callLog->to;

	#1 Identify internal calls
	if(isInternalCall($callLog)) {
		$fromExt = getCallerExtension($from, $extensions, $phoneNumbers);
		$toExt = getCallerExtension($to, $extensions, $phoneNumbers);
		return array('from'=>$fromExt, 'to'=>$toExt);
	}

	$ownerExt = null;

	#2 Find by extensions in the legs
	$extIds=array();
	if(isset($callLog->legs)) {
		foreach($callLog->legs as $leg) {
			if(isset($leg->extension->id) && !in_array($leg->extension->id, $extIds)) {
				array_push($extIds, $leg->extension->id);
			}
		}
	}
	if(count($extIds)==1) {
		$ownerExt = getExtensionById($extIds[0], $extensions);
	}

	#3 Find by from/to number
	if(!isset($ownerExt)) {
		$ownerCaller = $callLog->direction == 'Inbound' ? $callLog->to : $callLog->from;
		$ownerExt = getCallerExtension($ownerCaller, $extensions, $phoneNumbers);
	}

	return $ownerExt;
}


function getCallerExtension($caller, $extensions, $phoneNumbers) {
	$ext = null;
	if(isset($caller->extensionNumber)) {
		$ext = getExtensionByNumber($caller->extensionNumber, $extensions);
	}

	if(!isset($ext) && isset($caller->phoneNumber)) {
		$phone = getPhoneNumberInfo($caller->phoneNumber, $phoneNumbers);
		if(isset($phone->extension->id)) {
			$ext = getExtensionById($phone->extension->id, $extensions);
		}
	}

	if(!isset($ext) && isset($caller->name)) {
		foreach($extensions as $e) {
			if(isset($e->name) && ($e->name == $caller->name)) {
				$ext = $e;
				break;
			}
		}
	}
	return $ext;
}

function getPhoneNumberInfo($number, $phoneNumbers) {
	foreach($phoneNumbers as $pn) {
		if($pn->phoneNumber == $number) {
			return $pn;
		}
	}
}

function getExtensionByNumber($extensionNumber, $extensions) {
	foreach($extensions as $ext) {
		if(isset($ext->extensionNumber) && $ext->extensionNumber == $extensionNumber) {
			return $ext;
		}
	}
}

function getExtensionById($id, $extensions) {
	foreach($extensions as $ext) {
		if($ext->id == $id) {
			return $ext;
		}
	}
}

function isInternalCall($callLog) {
	return isset($callLog->from->extensionNumber);
}

function rcLog($logFile, $level, $message) {
    if($level >= $_ENV['Log_Level']) {
        $currentTime = date('Y-m-d H:i:s', time());
        $info = 'Info';
        if($level == 1){
            $info = 'Debug';
        }else if($level == 2) {
            $info = 'Error';
        }
        file_put_contents($logFile, $currentTime."[".$info."]"." -> ".$message.PHP_EOL, FILE_APPEND);
    }
}

$global_detailExtensions = array(); // id => extension detail
function getDetailExtension($id, $platform) {
	global $global_detailExtensions;
	if(isset($global_detailExtensions[$id])) {
		return $global_detailExtensions[$id];
	}
	// rate limit group light
	$detailExt = rcApiGet($platform, "/account/~/extension/$id", null)->json();
	$global_detailExtensions[$id]=$detailExt;
	return $detailExt;
}

// Convert call log startTime to DateTime object with timezone
function parseCallLogDate($callLog, $platform) {
	$callLog->startTime = new DateTime($callLog->startTime);
	if(isset($callLog->extension)) {
		$extId = $callLog->extension->id;
	} else if(isset($callLog->extensions['from'])) {
		$extId = $callLog->extensions['from']->id;
	} else {
		return;
	}
	$extInfo = getDetailExtension($extId, $platform);
	if(!isset($extInfo->regionalSettings->timezone->name)) {
		return;
	}
	$timezone = $extInfo->regionalSettings->timezone;
	$callLog->startTime->setTimezone(new DateTimeZone($timezone->name));
}
