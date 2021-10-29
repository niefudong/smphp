<?php

use Swoole\Coroutine\Client;
use function Swoole\Coroutine\run;

use Swoole\Coroutine\MySQL;


include_once "./../common.php";


run(function () {



    $client = new Client(SWOOLE_SOCK_TCP);
    if (!$client->connect('127.0.0.1', 9506, 0.5)) {
        echo "connect failed. Error: {$client->errCode}\n";
    }

    $login_data = [];
    $login_data['command'] = "01";
    $login_data['model_id'] = "1";
    $login_data['sn'] = "001";
    $sendMessage = json_encode($login_data);
    echo "client发送：{$sendMessage}\n";

    $sendMessage = intPack($sendMessage);

    
    $client->send($sendMessage);



    Swoole\Timer::tick(3*1000, function (int $timer_id, $client) {
        $heartCheckData = [];
        $swoole_mysql = swooleMysql();

        $res = $swoole_mysql->query("select `token` from `dev_device` where `sn`='001' limit 1;");
        if($res){
           
            $heartCheckData['command'] = "03";
            $heartCheckData['token'] = $res[0]['token'];
            $sendMessage = json_encode($heartCheckData);
            echo "client发送：{$sendMessage}\n";
            $sendMessage = intPack($sendMessage);
            $client->send($sendMessage);
        }

    }, $client);

    while (true) {
        $data = $client->recv();
        echo "client接收：{$data}\n";
        $data = intUnPack($data);
        echo "client接收：{$data}\n";
        if (strlen($data) > 0) {

            // $client->send(time());
            $sendMessage = "收到数据了：".time();

            echo "client发送：{$sendMessage}\n";
            $sendMessage = intPack($sendMessage);
            $client->send($sendMessage);
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







