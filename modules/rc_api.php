<?php

function rcIterateAllPages($platform, $url, $options, callable $cb) {
    $pageCount = 0;
    while(true) {
	$pageCount++;
        $options['page'] = $pageCount;
        $apiResponse = rcApiGet($platform, $url, $options);
        $result = $apiResponse->json();
        $records = $result->records;

        if (count($records)==0) {
                break;
        }

	$cb($result);

        if(isset($result->paging->totalPages)) {
            $totalPages = $result->paging->totalPages;
            $page = $result->paging->page;
            if($page >= $totalPages) {
                break;
            }
        }else if(!isset($result->navigation->nextPage)){
		echo "No next page\n";
		break;
        }
    }
    return $pageCount;
}

const RC_API_LIMIT = 40;
const RC_API_WINDOW = 60;
function rcApiControlRate() {
	static $windowStart;
	static $remaining = RC_API_LIMIT;
	if(!isset($windowStart)) {
		$windowStart=time();
	}
	if($remaining==0) {
		$windowLeft=$windowStart+RC_API_WINDOW-time();
		if($windowLeft>0) {
			echo "Rc API limit reached, waiting for $windowLeft s.\n";
			sleep($windowLeft);
		}
		$remaining=RC_API_LIMIT;
		$windowStart=time();
	}
}

// Wrapper of rc api get method, handle rate limit
function rcApiGet($platform, $url, $options) {
	rcApiControlRate();
	try{
		return $platform->get($url, $options);
	} catch(Exception $e) {
		$res=$e->apiResponse()->response();
		$status=$res->getStatusCode();
		if($status==429) {
			$retryAfter=(int)$res->getHeader('Retry-After')[0];
			echo "RingCentral API rate limit hit when get $url, wait for {$retryAfter}s.\n";
			sleep($retryAfter);
			return rcApiGet($platform, $url, $options);
		}
		if($status>=500 && $status<600) {
			$retryAfter=3;
			echo "Rc API server error: http $status, {$e->getMessage()}. Will retry in {$retryAfter}s.\n";
			sleep($retryAfter);
			return rcApiGet($platform, $url, $options);
		}
		throw $e;
	}
}
