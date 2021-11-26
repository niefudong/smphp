<?php

use Swoole\Coroutine\Client;
use function Swoole\Coroutine\run;

require __DIR__ . '/../../../vendor/autoload.php';

include_once __DIR__."/../functions.php";

use think\facade\Db;

use Noodlehaus\Config;

Db::setConfig(Config::load(ROOT_PATH.'/config/database.php'));


run(function () {

    $client = new Client(SWOOLE_SOCK_TCP);

    if (!$client->connect('127.0.0.1', 9505, 0.5)) {
        echo "connect failed. Error: {$client->errCode}\n";
    }

    $login_data = [];
    $login_data['command'] = "01";
    $login_data['sn'] = "c5test";
    $login_data['gsn'] = "1e0aa5a4-4db8-11ec-86d5-506b4bfdafd4";

    $sendMessage = json_encode($login_data);
    echo "[".date('Y-m-d H:i:s')."]\n";
    echo "client发送：{$sendMessage}\n";

    $sendMessage = packData($sendMessage);
 
    $client->send($sendMessage);

    Swoole\Timer::tick(5*1000, function (int $timer_id, $client) {

        $dev_sn = Db::table('dev_sn')->where('sn','c5test')->find();

        $heartCheckData = [];
           
        $heartCheckData['command'] = "03";
        $heartCheckData['token'] = $dev_sn['token'];
        $sendMessage = json_encode($heartCheckData);
        echo "[".date('Y-m-d H:i:s')."]\n";
        echo "client发送：{$sendMessage}\n";
        $sendMessage = packData($sendMessage);
        
        $client->send($sendMessage);
        

    }, $client);

    while (true) {
        $data = $client->recv();
        
       
        if (strlen($data) > 0) {

            $server_data = unpackData($data);

            echo "[".date('Y-m-d H:i:s')."]\n";
            echo "client接收：{$server_data}\n";
            $server_data = json_decode($server_data,true);
            if($server_data['command'] != "01" && $server_data['command'] != "02" && $server_data['command'] != "03"){
                $send_data = [
                    'response' => $server_data['command'],
                    'ret' => 0,
                    'code' => 0,
                    'id' => $server_data['id']
                ];
    
                $client->send(packData(json_encode($send_data)));
            }


        } else {
            if ($data === '') {
                // 全等于空 直接关闭连接
                $client->close();
                break;
            } else {
                if ($data === false) {
                    // 可以自行根据业务逻辑和错误码进行处理，例如：
                    // 如果超时时则不关闭连接，其他情况直接关闭连接
                    if ($client->errCode !== SOCKET_ETIMEDOUT) {
                        $client->close();
                        break;
                    }
                } else {
                    $client->close();
                    break;
                }
            }
        }
        \Co::sleep(1);
    }
});







