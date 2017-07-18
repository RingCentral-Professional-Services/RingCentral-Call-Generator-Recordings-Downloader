<?php

// Add owner extension info for each call log item
function populateCallLogOwner($callLog, $phoneNumbers, $extensions) {
	$ownerNumber=null;
	$owner=null;
	if($callLog->direction=="Inbound") {
		$owner=$callLog->to;
	} else {
		$owner=$callLog->from;
	}
	if(isset($owner->phoneNumber)) {
		$ownerNumber=$owner->phoneNumber;
	} else if(isset($owner->extensionNumber)) {
		$ownerNumber=$owner->extensionNumber;
	} else {
	//	echo "No owner info found.\n";
		$callLog->extension=null;
		return;
	}
	$callLog->extension=getExtension($ownerNumber, $phoneNumbers, $extensions, $callLog->legs);

	if(isset($callLog->from->extensionNumber)) {
		$callLog->from = getExtension($callLog->from->extensionNumber, $phoneNumbers, $extensions, $callLog->legs);
	}
}

function getExtension($number, $phoneNumbers, $extensions, $legs) {
    
    foreach($extensions as $ext) {
        if(property_exists($ext, 'extensionNumber')){
            if($number == $ext->extensionNumber) {
                return $ext;
            }    
        }
    }
    
    foreach ($phoneNumbers as $phoneNumber) {
        if($number == $phoneNumber->phoneNumber) {
            foreach ($extensions as $ext) {
                if(isset($phoneNumber->extension) && property_exists($phoneNumber->extension, 'extensionNumber') 
                    && property_exists($ext, 'extensionNumber')){
                    if($ext->extensionNumber == $phoneNumber->extension->extensionNumber) {
                        return $ext;
                    }    
                }
            }
        }
    }

    if(!is_null($legs)) {
        foreach ($legs as $leg) {
            if(property_exists($leg->to, 'phoneNumber')) {
                if($number == $leg->to->phoneNumber) {
                    if(property_exists($leg->to, 'name')) {
                        return $leg->to;
                    }
                }
            }
        }
    }
    
    return null;
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
		$extInfo = getDetailExtension($callLog->extension->id, $platform);
		if(!isset($extInfo->regionalSettings->timezone->name)) {
			return;
		}
		$timezone = $extInfo->regionalSettings->timezone;
		$callLog->startTime->setTimezone(new DateTimeZone($timezone->name));
	} else {
	}
}
