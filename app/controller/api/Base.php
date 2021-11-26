<?php
namespace app\controller\api;

class Base{

    public function __construct()
    {
        $data = $_POST;

        if($data['token'] != "GUANGFENGKEJI"){
            respond(10001, "签名错误");
        }

    }
    
    public function paramToStr($param,$token){
        unset($param['sign']);
        
        ksort($param);
        $paramStr = '';
        foreach ($param as $key => $value) {
            $paramStr .= $key.'='.$value.'&';
        }
        $paramStr .= 'key='.$token;
        return $paramStr;
    }

        
}