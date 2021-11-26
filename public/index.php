<?php
namespace sk;

header('Access-Control-Allow-Origin: *');

require __DIR__ . '/../vendor/autoload.php';

use think\facade\Db;
use Noodlehaus\Config;

Db::setConfig(Config::load(ROOT_PATH.'/config/database.php'));
 
$url = explode('/',$_SERVER['PATH_INFO']);

$mehod = array_pop($url);

$class_name = array_pop($url);

$class_name = ucfirst($class_name);

$class = array_push($url,$class_name);

$class = "\\app\\controller".implode("\\",$url);

try{
    
    $object = new $class();

    $object->$mehod();

}catch(\Exception $e){

    echo $e->getMessage();

    // respond(11111, $e->getMessage());

}