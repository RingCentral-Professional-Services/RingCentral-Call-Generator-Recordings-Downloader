<?php

$number = 'unknown';
$indicator = null;
$otherNumber = 'unknown';
$legs = null;
if(property_exists($callLog, 'legs')) {
    $legs = $callLog->legs;
}

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

$callLogOwner=null;
if(isset($callLog->extension)) {
	$callLogOwner=$callLog->extension->name;
} else {
	$callLogOwner=$number;
}
$callStartTime=$callLog->startTime;
$filePath="$callLogOwner/{$callStartTime->format('Y-m-d')}/{$indicator}_{$otherNumber}_{$callStartTime->format('H:i:s')}_{$callLog->recording->id}";
echo "Call recording file path: $filePath\n";
