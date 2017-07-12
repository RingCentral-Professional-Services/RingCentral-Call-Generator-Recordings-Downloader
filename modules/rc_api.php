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

// Wrapper of rc api get method, handle rate limit
function rcApiGet($platform, $url, $options) {
	try{
		return $platform->get($url, $options);
	} catch(Exception $e) {
		$res=$e->apiResponse()->response();
		$status=$res->getStatusCode();
		$retryAfter=(int)$res->getHeader('Retry-After')[0];
		if($status==429) {
			echo "RingCentral API rate limit hit when get $url, wait for $retryAfter seconds.\n";
			sleep($retryAfter);
			return rcApiGet($platform, $url, $options);
		}
		throw $e;
	}
}
