<?php

use Swoole\Coroutine\MySQL;

include __DIR__."/../../public/public.php";

function config($string=""){
    if($string){
        $string_array = explode('.',$string);
        foreach($string_array as $k=>$v){
            if($k==0){
                $data = include(ROOT_PATH."config/".$v.".php");
            }else{
                $data = &$data[$v];
            }
        }
    }

    return $data;
}

function setToken(){
    $str = md5(uniqid(md5(microtime(true)), true));
    $str = sha1($str);
    //加密
    return $str;
}

function intPack($data,$num=8){
    return sprintf("%0".$num."d", strlen($data)).$data;
}

function intUnPack($data,$num=8){
    return substr($data,$num);
}

//对数据信息封装
function packData($sendData,$packModel="N"){
   
    return pack($packModel,strlen($sendData)).$sendData;
}

//解包
function unpackData($rcvData,$packModel="N"){


    return mb_strcut($rcvData, packHeadByteNum($packModel));

}

function packHeadByteNum($packModel){
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

function swooleMysql(){
    $swoole_mysql = new MySQL();

    // var_dump(config("config"));
    // var_dump(config("config.mysql"));
    // var_dump(config("config.mysql.host"));

    $swoole_mysql->connect(config("config.mysql"));

    return $swoole_mysql;
}