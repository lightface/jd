<?php
$urls = array(
    5=>"http://item.taobao.com/item.htm?id=14392877692",
    6=>'http://item.taobao.com/item.htm?id=14392877695'
);

function callback($data, $delay) {
    preg_match('/<title>(.+)<\/title>/iU', $data, $matches);
    usleep($delay);
    return $matches[1];
}

function rolling_curl($urls, $delay) {
    $queue = curl_multi_init();
    $map = array();

    foreach ($urls as $id=>$url) {
        $ch = curl_init();

        curl_setopt_array($ch,[
            CURLOPT_URL => $url,
            CURLOPT_HTTPHEADER => $header,
            CURLOPT_USERAGENT => $_SERVER['HTTP_USER_AGENT'],
            CURLOPT_ENCODING => 'gzip,deflate',
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_HEADER => 0,
//                CURLOPT_WRITEFUNCTION => array($this,'receivePartial'),
            CURLOPT_NOSIGNAL => 1,
            CURLOPT_VERBOSE => 1,
        ]);

        curl_multi_add_handle($queue, $ch);
        $map[(string) $ch] = $id;
    }

    $results = array();
    do {
        while (($code = curl_multi_exec($queue, $active)) == CURLM_CALL_MULTI_PERFORM) ;

        if ($code != CURLM_OK) { break; }

        // a request was just completed -- find out which one
        while ($done = curl_multi_info_read($queue)) {

            // get the info and content returned on the request
            $results[$map[(string) $done['handle']]] = callback(curl_multi_getcontent($done['handle']), $delay);

            // remove the curl handle that just completed
            curl_multi_remove_handle($queue, $done['handle']);
            curl_close($done['handle']);
        }

        // Block for data in / output; error handling is done by curl_multi_exec
        if ($active > 0) {
            curl_multi_select($queue, 0.5);
        }

    } while ($active);

    curl_multi_close($queue);
    return $results;
}

$result = rolling_curl($urls, 3);
echo  '<pre>';
print_r($result);