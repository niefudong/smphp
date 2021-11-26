<?php

/**
 * Curl版本
 * 使用方法：
 * $url
 * $data
 */
if (!function_exists('request_by_curl')) {

    function request_by_curl($url , $data = []){

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);

        // POST数据
        curl_setopt($ch, CURLOPT_POST, 1);
        // 把post的变量加上
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

        $output = curl_exec($ch);

        curl_close($ch);

        return $output;

    }

}

/**
 * Socket版本
 * 使用方法：
 * $post_string = "app=socket&version=beta";
 * request_by_socket('chajia8.com', '/restServer.php', $post_string);
 */
if (!function_exists('request_by_socket')) {

    function request_by_socket($remote_server, $remote_path, $post_string, $port = 80, $timeout = 30){

        $socket = fsockopen($remote_server, $port, $errno, $errstr, $timeout);
        if (!$socket) die("$errstr($errno)");
        fwrite($socket, "POST $remote_path HTTP/1.0");
        fwrite($socket, "User-Agent: Socket Example");
        fwrite($socket, "HOST: $remote_server");
        fwrite($socket, "Content-type: application/x-www-form-urlencoded");
        fwrite($socket, "Content-length: " . (strlen($post_string) + 8) . "");
        fwrite($socket, "Accept:*/*");
        fwrite($socket, "");
        fwrite($socket, "mypost=$post_string");
        fwrite($socket, "");
        $header = "";
        while ($str = trim(fgets($socket, 4096))) {
            $header .= $str;
        }
        $data = "";
        while (!feof($socket)) {
            $data .= fgets($socket, 4096);
        }
        return $data;
    }

}

if (!function_exists('request_by_http')) {
    /**
     * 发送post请求
     * @param string $url 请求地址
     * @param array $post_data post键值对数据
     * @return string
     */
    function request_by_http($url, $post_data) {
        $postData = http_build_query($post_data);
        $options = array(
            'http' => array(
                'method' => 'POST',
                'header' => 'Content-type:application/x-www-form-urlencoded',
                'content' => $postData,
                'timeout' => 15 * 60 // 超时时间（单位:s）
            )
        );
        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);

        return $result;
    }
}

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\FirePHPHandler;


if(!function_exists("logs")){

    function logs($log = "logs"){
        // Create the logger
        $logger = new Logger($log);
        // Now add some handlers
        $logger->pushHandler(new StreamHandler(ROOT_PATH."/runtime/log/".$log."/".date('Y')."/".date('m')."/".date('d').".log", Logger::DEBUG));
        $logger->pushHandler(new FirePHPHandler());
        return $logger;
    }

}