<?php

function requestMultiPages($platform, $url, $options) {
    
    $results = array();
    
    $pageCount = 1;
    $flag = true;
    
    while($flag) {
        
        $options['page'] = $pageCount;
        $apiResponse = $platform->get($url, $options);
        $apiResponseJSONArray = $apiResponse->json();
        $records = $apiResponseJSONArray->records;

	if (count($records)==0) {
		break;
	}
        
        foreach ($records as $record) {
            array_push($results, $record);
        } 
        
        if(property_exists($apiResponseJSONArray->paging, 'totalPages')) {
            $totalPages = $apiResponseJSONArray->paging->totalPages;
            $page = $apiResponseJSONArray->paging->page;
            if($page <= $totalPages) {
                $pageCount = $pageCount + 1;
                if($page == $totalPages) {
                    $flag = false;
                }
            }
        }else {
            if(isset($apiResponseJSONArray->navigation->nextPage)){
                $pageCount = $pageCount + 1;
            }else{
                $flag = false;
            }
        }
    }
    
    return $results;
}

function retrieveRecording($platform, $callLog) {
    $uri = $callLog['recordingUrl'];
    $apiResponse = $platform->get($uri);
    $ext = ($apiResponse->response()->getHeader('Content-Type')[0] == 'audio/mpeg')
        ? 'mp3' : 'wav';
    return array(
        'ext' => $ext,
        'data' => $apiResponse->raw()
    );
}

// Add owner extension info for each call log item
function populateCallLogsOwner($callLogs, $phoneNumbers, $extensions) {
	foreach($callLogs as $callLog) {
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
			continue;
		}
		$callLog->extension=getExtension($ownerNumber, $phoneNumbers, $extensions, $callLog->legs);
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
	$detailExt = $platform->get("/account/~/extension/$id")->json();
	$global_detailExtensions[$id]=$detailExt;
	sleep(1); // delay the execution to avoid rate limit
	return $detailExt;
}

// Convert call log startTime to DateTime object with timezone
function parseCallLogsDate($callLogs, $platform) {
	$total=count($callLogs);
	$count=0;
	foreach($callLogs as $callLog) {
		$count++;
		echo "Parsing call log date $count/$total.\r";
		$callLog->startTime = new DateTime($callLog->startTime);
		if(isset($callLog->extension)) {
			$timezone = getDetailExtension($callLog->extension->id, $platform)->regionalSettings->timezone;
			$callLog->startTime->setTimezone(new DateTimeZone($timezone->name));
		} else {
		}
	}
	echo "\n";
}
