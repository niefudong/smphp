<?php
namespace sk;

class Log{

    public static function record($string="",$type="info"){

        $log_dir = ROOT_PATH."/runtime/log/".$type."/".date('Y')."/".date('m');
        $log_file = ROOT_PATH."/runtime/log/".$type."/".date('Y')."/".date('m')."/".date('d').".log";

        if(!is_dir($log_dir)){

            mkdir($log_dir,0777,true);

        }
        $str = "[".date('Y-m-d H:i:s')."]";

        $str .= "[".$type."]";
        $str .= " ";
        $str .= $string;
        $str .= PHP_EOL;
        file_put_contents($log_file,$str, FILE_APPEND);

    }
    
}