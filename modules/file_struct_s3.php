<?php

$number = 'unknown';
$indicator = null;
$otherNumber = 'unknown';

if($callLog->direction == "Inbound") {
    $indicator = "from";
    if(property_exists($callLog->to, 'phoneNumber')){
        $number = $callLog->to->phoneNumber;
    }else{
        if(property_exists($callLog->to, 'extensionNumber')){
            $number = $callLog->to->extensionNumber;
        }
    }
    if(property_exists($callLog->from, 'phoneNumber')){
        $otherNumber = $callLog->from->phoneNumber;
    }else{
        if(property_exists($callLog->from, 'extensionNumber')){
            $otherNumber = $callLog->from->extensionNumber;
        }
    }
}else{
    $indicator = "to";
    if(property_exists($callLog->from, 'phoneNumber')){
        $number = $callLog->from->phoneNumber;
    }else{
        if(property_exists($callLog->from, 'extensionNumber')){
            $number = $callLog->from->extensionNumber;
        }
    }
    if(property_exists($callLog->to, 'phoneNumber')){
        $otherNumber = $callLog->to->phoneNumber;
    }else{
        if(property_exists($callLog->to, 'extensionNumber')){
            $otherNumber = $callLog->to->extensionNumber;
        }
    }
}

$extension = getExtension($number, $phoneNumbers, $accountExtensions);
if(!is_null($extension)){
    $filePath = ($extension->name).'/'.substr($callLog->startTime, 0, 10).'/'.
        $indicator."_".$otherNumber."_".substr($callLog->startTime, 11, 8)."_".$callLog->recording->id;
}else {
    $filePath = $number.'/'.substr($callLog->startTime, 0, 10).'/'.
        $indicator."_".$otherNumber."_".substr($callLog->startTime, 11, 8)."_".$callLog->recording->id;
}