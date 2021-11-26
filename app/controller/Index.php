<?php

namespace app\controller;

use think\facade\DB;

use Noodlehaus\Config;
use Noodlehaus\Parser\Json;

use Desarrolla2\Cache\File as Cache;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\FirePHPHandler;

use WebGeeker\Validation\Validation;

class Index{

    public function test(){
        

        $rule = [
            // "id" => "Required",
            "d" => "Required|StrLen:11",
        ];

        Validation::validate($_GET,$rule);

       

        // Create the logger
        $logger = new Logger('log');
        // Now add some handlers
        $logger->pushHandler(new StreamHandler(ROOT_PATH.'/runtime/log/'.date('Ym').'/'.date('d').'.log', Logger::DEBUG));
        $logger->pushHandler(new FirePHPHandler());

        // You can now use your logger
        $logger->info('My logger is now ready');
        $logger->error('My logger is now ready');

        $cache = new Cache(ROOT_PATH."/runtime/cache");

                   
        // $cache->set('key', "dddddd", 3600);

        $value = $cache->get('key');

        dump($value);

        // Load a single file
        $conf = Config::load(ROOT_PATH.'/config/database.php');

        dump($conf);

        dump($conf['default']);
        dump($conf['connections']);
        dump($conf['connections']['mysql']);
        dump($conf['connections']['mongo']);

        $lists = Db::table('dev_queue')->select();

        dump($lists);


    }
}