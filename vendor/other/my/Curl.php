<?php

namespace my;
use DOMDocument;
use my\QW;

class Curl {
    public $data_c;//每次抓取的数据
    public $header = [
        "Accept-Encoding: gzip,deflate",
        "User-Agent: Dalvik/1.6.0 (Linux; U; Android 4.1.1; MI 2SC MIUI/4.12.5)"
//        "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8",
//        "Host: item.m.jd.com",
//        "Accept-Encoding: gzip, deflate, sdch",
//        "Accept-Language: zh-CN,zh;q=0.8,en;q=0.6",
//        "Cache-Control: max-age=0",
//        "Connection: keep-alive",
//        "Accept-Language: en-us,en;q=0.5",
//        "Referer:".$url,
//        "X-FORWARDED-FOR: ".get_ip(),
//        "CLIENT-IP: ".get_ip(),
    ];
//    public $goods;//抓取的数据放在这个里面

    /**
     * 抓取一个网址的数据并处理出来价格名字
     * @param $url需要抓取的地址
     * @return bool
     */
    public function catch_one($url){
        global $data_c;
        $data_c = '';
        $content = $this->grab_url($url);

//        $len =  strlen($content).'<br/>';
//	echo (microtime(TRUE)-START_TIME).'<br/>';


        return $this->content_process($content);


    }

    /**
     * 并发抓取一组网址的数据
     * @param $urlarr需要抓取的地址--数组
     * @return array
     */
    public function catch_multi($urlarr=array()){
        global $data_c;
        $mh = curl_multi_init();
        $map = array();
        foreach ($urlarr as $id => $url) {
            $timeout = 5;
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_HTTPHEADER => $this->header,
                CURLOPT_USERAGENT => "Dalvik/1.6.0 (Linux; U; Android 4.1.1; MI 2SC MIUI/4.12.5)",
                CURLOPT_COOKIE => "pin=lightface;wskey=AAFWFRkSAEAv1eVybBPHTxHyftCeKW_u-6ojsCrGY2ohtP6nIKmCiy6Zo5W7Y64ZQ5Aq3okAicQbnFdl_bO7WCagazFksmXI",
                CURLOPT_ENCODING => 'gzip,deflate',
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_HEADER => 0,
                CURLOPT_WRITEFUNCTION => array($this, 'receivePartial'),
                CURLOPT_NOSIGNAL => 1,
                CURLOPT_TIMEOUT_MS => $timeout * 1000,
                CURLOPT_VERBOSE => 1,
            ]);
            curl_multi_add_handle($mh, $ch);
            $map[(string)$ch] = $id;
        }
            $result = array();
            do {
                while (($code = curl_multi_exec($mh, $active)) == CURLM_CALL_MULTI_PERFORM) ;

                if ($code != CURLM_OK) { break; }

                // a request was just completed -- find out which one
                while ($done = curl_multi_info_read($mh)) {

                    // get the info and content returned on the request
                    curl_multi_getcontent($done['handle']);
                    $data = $this->content_process($data_c);
                    $data_c = '';
                    if(!empty($data)){
                        $result[$map[(string) $done['handle']]] = $data;
                        echo 'id:'.$map[(string) $done['handle']].'  '.date('H:i:s',time()).'  '.'产品：'.$data['name'].'   '.'价格：'.$data['price'].'<br/>';
                    @ob_flush();
                    @flush();
                    }
                    // remove the curl handle that just completed
                    curl_multi_remove_handle($mh, $done['handle']);
                    curl_close($done['handle']);
                }
                if ($active > 0) {
                    curl_multi_select($mh, 0.5);
                }
            } while ($active);
        curl_multi_close($mh);
        return  $result;
    }

    /**
     * 处理content返回name和price
     * @param $content
     * @return array|bool
     */
    public function content_process($content){
        $content = mb_convert_encoding($content, 'HTML-ENTITIES', 'utf-8');
        $data = array();
        preg_match('/class="prod-price">[\s\S]+?<\/span>([\s\S]+?)<\/div>/is',$content,$good_price);
        if(!empty($good_price[1]) && !empty(floatval($good_price[1]))){
            $data['price'] = floatval($good_price[1]);

            libxml_use_internal_errors(true);
            $dom = new DOMDocument();
            $dom->loadHTML($content);
            libxml_use_internal_errors(false);
//        $dom->loadHTML("<html><body>Test<br></html>");
            $xml = simplexml_import_dom($dom);

//	$good = $xml->xpath('//div[@id="name"]/..');
            $good_name = $xml->xpath('//div[@class="prod-title"]');
            $good_name = $this->xml2array($good_name);
            if(!empty($good_name[0]['@attributes']['class']) && $good_name[0]['@attributes']['class'] == 'prod-title' && !empty($good_name[0]['a']['span'][0]) && is_string($good_name[0]['a']['span'][0])){
                $data['name'] = addslashes($good_name[0]['a']['span'][0]);
            }
        }

        if(!isset($data['name']) || !isset($data['price'])){
            return false;
        }

        return $data;
    }
    //抓取url内容
    public function grab_url($url) {
        $result = $this->server_gp($url, NULL, 5, TRUE);

        if ($result['http_code'] != '200') {
            sleep(2);
            $result = $this->server_gp($url, NULL, 5, TRUE);

            if ($result['http_code'] != '200') {
                echo $result['http_code'].'<br />';
                exit('get page error<br />url:'.$url);
            }
        }
//	return to_utf8($result['body']);
        return ($result['body']);
    }

    /**
     * 服务端向外请求
     * @param	$url: url地址
     * @param	$post: POST数据
     * @param	$timeout: 超时时间
     * @param	$accept_cookie: 是否接收对方服务器发送的cookie
     * @return	array
     */
    public function server_gp($url, $post = array(), $timeout = 5, $accept_cookie = FALSE) {
        global $data_c;

        $curl = curl_init();

        if (stripos($url,"https://") !== FALSE) {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        }

        curl_setopt_array($curl,[
            CURLOPT_URL => $url,
            CURLOPT_HTTPHEADER => $this->header,
            CURLOPT_USERAGENT => $_SERVER['HTTP_USER_AGENT'],
            CURLOPT_ENCODING => 'gzip,deflate',
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_HEADER => 0,
            CURLOPT_WRITEFUNCTION => array($this,'receivePartial'),
            CURLOPT_NOSIGNAL => 1,
            CURLOPT_TIMEOUT_MS => $timeout * 1000,
            CURLOPT_VERBOSE => 1,
        ]);

        if (!empty($post)) {
            if (is_array($post)) {
                $data = array();
                foreach ($post as $key => $val) {
                    $data[$key] = (!is_object($val) && substr($val, 0, 1) != '@') ? urlencode($val) : $val;
                }
            } else if (is_string($post)) {
                $data = &$post;
            } else {
                $data = array();
            }

            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }

        /*if ($accept_cookie) {
            $cookie_file = WEB_ROOT.'data/cookie/cookie_'.$GLOBALS['member']['sid'].'.txt';
            if (is_readable($cookie_file)) {
                curl_setopt($curl, CURLOPT_COOKIEFILE, $cookie_file);
            }
            curl_setopt($curl, CURLOPT_COOKIEJAR, $cookie_file);
        }*/
        curl_exec($curl);//$data_c
        $result['body'] = $data_c;
        $result['http_code'] = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);
        return $result;
    }

    public function receivePartial($ch, $chunk) {
        global $data_c;
        $data_c .= $chunk;

        $len = strlen($chunk);
//        echo '+'.'<br/>';
//        echo $len.'<br/>';
//        echo strlen($data_c).'<br/>';
        //判断每次读取,如果总数大于10000,就不再往下读了.
        if (strlen($data_c) >= 10000) {
            return -1;
        }
        //返回值是告知CURL,是否已够了,要不要再读啦.
        return $len;
    }



    /**
     * 抓取内容gbk 转urf-8
     * @param $content
     * @return mixed
     */
    public function to_utf8(&$content) {
        $content = mb_convert_encoding($content, 'utf-8', 'gbk');
        if (preg_match('/&#\d{5}/', $content)) {
            $content = mb_convert_encoding($content, 'utf-8', 'HTML-ENTITIES');
        }

        return str_ireplace(
            array('charset="gbk"', 'charset=GBK', 'lang="GBK"'),
            array('charset="utf-8"', 'charset=utf-8', 'lang="utf-8"'),
            $content
        );
    }

    /**
     * xml对象转为数组
     * @param $xml_obj
     * @return mixed
     */
    public function xml2array($xml_obj) {
        return json_decode(json_encode((array)$xml_obj), TRUE);
    }

    /**
     * 等待开发
     * @param $message
     * @param string $url
     * @param int $wait_seconds
     */
    public function show_message($message, $url = '', $wait_seconds = 3) {
        $wait_seconds = $wait_seconds * 1000;
//        include WEB_ROOT.'manage/template/tpl_showtips_grab.php';
        exit();
    }
}