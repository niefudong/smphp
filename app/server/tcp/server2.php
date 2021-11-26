<?php

use Swoole\Coroutine\MySQL;
use function Swoole\Coroutine\run;

include_once "./../common.php";

//创建Server对象，监听 127.0.0.1:9506 端口
$server = new Swoole\Server('0.0.0.0', 9506);

$server->set(array(
    'reactor_num' => 2, //线程数
    'worker_num' => 2,  //进程数
    'heartbeat_check_interval' => 5,//每5秒侦测一次心跳
    'heartbeat_idle_time' => 10,//一个TCP连接如果在10秒内未向服务器端发送数据，将会被切断
    'max_connection' => 10000,  //最大连接数
    'enable_coroutine' => true, //是否启用异步风格服务器的协程支持
    'open_length_check' => true,    //打开包长检测特性
    'package_max_length' => 81920,  //设置最大数据包尺寸，单位为字节
    // 'dispatch_mode'       => 1,
    'package_length_func' => function ($data) {

      if (strlen($data) < 8) {
          return 0;
      }
      $length = intval(trim(substr($data, 0, 8)));
      if ($length <= 0) {
          return -1;
      }
      return $length + 8;
    },
));


//监听连接进入事件
$server->on('Connect', function ($server, $fd){
    echo "Client: Connect.\n";
    $response_data = [];
    $response_data['ret'] = "0";
    $response_data['msg'] = "connect success";
    $response_data = json_encode($response_data); 
    echo "server发送：{$response_data}\n";
    $response_data = intPack($response_data);
   
    $server->send($fd,$response_data);

});

//监听数据接收事件
$server->on('Receive', function ($server, $fd, $reactor_id, $data){

    $swoole_mysql = swooleMysql();
    echo "server接收：{$data}\n";
    
    $data = intUnPack($data);

    $client_data = json_decode($data,true);

    if(is_array($client_data)){
        if(array_key_exists("command",$client_data)){

            
            if($client_data['command'] == "01"){

                $res = $swoole_mysql->query("select * from `dev_model` where `id`='{$client_data['model_id']}';");
                
                if(!$res){  //如果未找到型号id
                    
                    $response_data = [];
                    $response_data['ret'] = "0";
                    $response_data['code'] = "1001";
                    $response_data = json_encode($response_data);
                    echo "server发送：{$response_data}\n";
                    $response_data = intPack($response_data);
                    
                    $server->send($fd,$response_data);  
                }else{
                   
                    $res = $swoole_mysql->query("select * from `dev_device` where `sn`='{$client_data['sn']}';");
                    
                    $token = setToken();
                    if(!$res){
                        $time_now = time();
                       
                        $swoole_mysql->query("insert into `dev_device` (`sn`,`token`,`on_status`,`fd`,`create_time`,`update_time`) values ('{$client_data['sn']}','{$token}','0',{$fd},{$time_now},{$time_now});");
                    }else{
                        
                        $swoole_mysql->query("update `dev_device` set `token`='{$token}',`on_status`='0',`fd`='{$fd}' where `sn`='{$client_data['sn']}';");
                        $response_data = [];
                        $response_data['ret'] = "0";
                        $response_data['token'] = $token;
                        $response_data['code'] = "10000";

                        $response_data = json_encode($response_data);
                        echo "server发送：{$response_data}\n";
                        $response_data = intPack($response_data);
                      
                        $server->send($fd,$response_data);
                    }

                }


            }else if($client_data['command'] == "02"){
                $response_data = [];
                $response_data['ret'] = "0";
                $response_data['code'] = "10000";

                $response_data = json_encode($response_data);
                echo "server发送：{$response_data}\n";
                $response_data = intPack($response_data);
               
                $server->send($fd,$response_data);
            }else if($client_data['command'] == "03"){
                $response_data = [];
                $response_data['ret'] = "0";
                $response_data['code'] = "10000";
                $response_data = json_encode($response_data);
                echo "server发送：{$response_data}\n";
                $response_data = intPack($response_data);
                
                $server->send($fd,$response_data);

            }
        }

    }


});

//监听连接关闭事件
$server->on('Close', function ($server, $fd) {

    $swoole_mysql = swooleMysql();

    $swoole_mysql->query("update `dev_device` set `on_status`=1,`fd`=0 where `fd`={$fd}");

    echo "Client: Close.\n";


});

$server->on('WorkerStart', function ($server, $worker_id) {


    Swoole\Timer::tick(1*1000, function (int $timer_id, $server) {
        $closeFdArr = $server->heartbeat(false);
        foreach ($closeFdArr as $fd) {
           $server->close($fd);
        }
    }, $server);

});

//启动服务器
$server->start(); 