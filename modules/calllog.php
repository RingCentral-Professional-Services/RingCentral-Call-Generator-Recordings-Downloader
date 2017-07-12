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



