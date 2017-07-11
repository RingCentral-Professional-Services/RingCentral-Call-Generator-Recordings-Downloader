<?php

function getAllExtensions($platform){
	$extensions=array();
	rcIterateAllPages($platform, '/account/~/extension', array('perPage' => 1000), function($result) use(&$extensions){
		$extensions=array_merge($extensions, $result->records);
	});
	return $extensions;
}

function getAllPhoneNumbers($platform){
        $phoneNumbers=array();
        rcIterateAllPages($platform, '/account/~/phone-number', array('perPage' => 1000, 'usageType' => 'DirectNumber'), function($result)use(&$phoneNumbers){
                $phoneNumbers=array_merge($phoneNumbers, $result->records);
        });
        return $phoneNumbers;
}

