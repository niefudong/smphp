<?php


if(!function_exists("sendData")){
    
    function sendData($server,$fd,$response_data){

        $response_data = json_encode($response_data,256);
        
        //写入日志
        logs('swoole_tcp')->info("server发送：{$response_data}",['fd'=>$fd]);
    
        $response_data = packData($response_data);
    
        $server->send($fd,$response_data);
    }

}

if (!function_exists('setToken')) {
    function setToken(){
        $str = md5(uniqid(md5(microtime(true)), true));
        $str = sha1($str);
        //加密
        return $str;
    }
}

if (!function_exists('intPack')) {
    function intPack($data,$num=8){
        return sprintf("%0".$num."d", strlen($data)).$data;
    }
}

if (!function_exists('intUnPack')) {
    function intUnPack($data,$num=8){
        return substr($data,$num);
    }
}


if (!function_exists('packData')) {
    //对数据信息封装
    function packData($sendData,$packModel="N"){
    
        return pack($packModel,strlen($sendData)).$sendData;
    }
}

if (!function_exists('unpackData')) {
    //解包
    function unpackData($rcvData,$packModel="N"){

        return mb_strcut($rcvData, packHeadByteNum($packModel));

    }
}

if (!function_exists('packHeadByteNum')) {
    function packHeadByteNum($packModel="N"){
        $byteNum = 0;
        switch($packModel){
            case "N":
                $byteNum = 4;
                break;
            case "C":
                $byteNum = 1;
                break;
        }
        return $byteNum;
    }
}