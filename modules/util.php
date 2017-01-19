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