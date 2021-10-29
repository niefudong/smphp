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
    $login_data['model_id'] = "1";
    $login_data['sn'] = "003";
    $login_data['status'] = "01";
    $sendMessage = json_encode($login_data);
    echo "client发送：{$sendMessage}\n";

    $sendMessage = packData($sendMessage,"N");

    
    $client->send($sendMessage);



    while (true) {
        $data = $client->recv();
        $data = unpackData($data,"N");
        echo "client接收：{$data}\n";
        if (strlen($data) > 0) {

            // $client->send(time());
            $sendMessage = "收到数据了：".time();

            echo "client发送：{$sendMessage}\n";
            $sendMessage = packData($sendMessage,"N");
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







