<?php

require __DIR__ . '/../../../vendor/autoload.php';

include_once __DIR__."/../functions.php";

use think\facade\Db;

use Noodlehaus\Config;

Db::setConfig(Config::load(ROOT_PATH.'/config/database.php'));

date_default_timezone_set('PRC');

//创建Server对象，监听 127.0.0.1:9505 端口
$server = new Swoole\Server('0.0.0.0', 9505);

$server->set(array(
    'reactor_num' => 2, //线程数
    'worker_num' => 2,  //进程数
    'heartbeat_check_interval' => 15,//每5秒侦测一次心跳
    'heartbeat_idle_time' => 30,//一个TCP连接如果在10秒内未向服务器端发送数据，将会被切断
    'max_connection' => 10000,  //最大连接数
    'enable_coroutine' => true, //是否启用异步风格服务器的协程支持
    'open_length_check' => true,    //打开包长检测特性
    'package_max_length' => 81920,  //设置最大数据包尺寸，单位为字节
    'package_length_type' => "N", //see php pack() 长度值的类型，接受一个字符参数，与 PHP 的 pack 函数一致
    'package_length_offset' => 0, // length 长度值在包头的第几个字节
    'package_body_offset' => packHeadByteNum(), // 从第几个字节开始计算长度，一般有 2 种情况
));


//监听连接进入事件
$server->on('Connect', function ($server, $fd) {
    echo "Client: Connect.\n";

});


//监听数据接收事件
$server->on('Receive', function ($server, $fd, $reactor_id, $data) {

    $clientInfo = $server->getClientInfo($fd);
    
    $data = unpackData($data);

    //日志
    logs('swoole_tcp')->info("server接收：{$data}",['fd'=>$fd]);

    $client_data = json_decode($data,true);

    if(is_array($client_data)){//如果数据是数组

        if(array_key_exists("command",$client_data)){//设备连接

            // 写入设备日志
            $dev_log_data = [
                'gsn' => $client_data['gsn'] ?? "", 
                'log_type'=> $client_data['command'], 
                'log_content' => $data,
                'log_time' => time(),
                'token'=> $client_data['token'] ?? "",
                'gps' => $client_data['last_gps'] ?? "",
                'ip' => $clientInfo['remote_ip']
            ];
            Db::table('dev_log')->insert($dev_log_data);

            if($client_data['command'] == "01"){//command为01 代表注册
                
                //检查是否缺少参数
                if(!array_key_exists('sn',$client_data) || !array_key_exists('gsn',$client_data)){

                    $response_data = [
                        'command' => $client_data['command'],
                        'ret' => "0",
                        'code' => "10001",
                        'msg' => "missing params",
                    ];
                    sendData($server,$fd,$response_data);

                }else{

                    //通过gsn 判断是否第一次注册
                    if($client_data['gsn'] == ""){//gsn为空，只能返回gsn 或错误；

                        $time_now = time();
    
                        if($client_data['sn']){
                            
                            $mysql_device_check = Db::connect('mysql')->table('dev_device')->where('sn',$client_data['sn'])->find();
    
                            if(!$mysql_device_check){//如果设备从未添加过，添加设备，并返回gsn

                                $res = Db::connect('mysql')->query("insert into `dev_device` (`id`,`sn`,`on_status`,`fd`,`create_time`,`update_time`) values (uuid(),'{$client_data['sn']}','0',{$fd},{$time_now},{$time_now});");
                    
                                logs('swoole_tcp')->info("insert into `dev_device` (`id`,`sn`,`on_status`,`fd`,`create_time`,`update_time`) values (uuid(),'{$client_data['sn']}','0',{$fd},{$time_now},{$time_now});");
                                $device = Db::connect('mysql')->table('dev_device')->where('sn',$client_data['sn'])->order('create_time desc')->find();
                                
                                if($res !== false){//如果mysql数据库添加数据成功

                                    $gsn = $device['id'];
                                   
                                    $mongodb_device_data = [
                                        'gsn' => $gsn,
                                        'sn' => $client_data['sn'], 
                                        'sn_model' => "",
                                        'token' => "", 
                                        'sys_status' => 0,
                                        'on_stauts' => 0,
                                        'last_gps' => $client_data['last_gps'] ?? "",
                                        'last_ip' => $clientInfo['remote_ip'],
                                        'fd' => ""
                                    ];
                                    Db::table('dev_sn')->insert($mongodb_device_data);

                                    $response_data = [
                                        'command' => $client_data['command'],
                                        'ret' => "0",
                                        'code' => "10000",
                                        'gsn' => $gsn
                                    ];

                                }else{
                                    $response_data = [
                                        'command' => $client_data['command'],
                                        'ret' => "0",
                                        'code' => "10001",
                                        'msg' => "register failed"
                                    ];
                                }

                        
                            }else{//如果设备曾经添加过

                                if($mysql_device_check['owner_user']){//设备已认领，返回gsn

                                    $response_data = [
                                        'command' => $client_data['command'],
                                        'ret' => "0",
                                        'code' => "10000", 
                                        'gsn' => $mysql_device_check['id'], 
                                    ];

                                }else{//设备未认领

                                    $response_data = [
                                        'command' => $client_data['command'],
                                        'ret' => "0",
                                        'code' => "10002",
                                        'msg' => "Equipment not certified",
                                    ];

                                }
                            }                        
                            sendData($server,$fd,$response_data);
    
                        }

                        $server->close($fd);
                
                    }else{//gsn不为空

                        $mysql_device = Db::connect('mysql')->table('dev_device')->where('id',$client_data['gsn'])->find();

                        if(!$mysql_device){//通过gsn 未找到设备

                            $response_data = [
                                'command' => $client_data['command'],
                                'ret' => "0",
                                'code' => "10003",
                                'msg' => "Unique encoding error",
                            ];
                        
                            sendData($server,$fd,$response_data);

                        }else{//通过gsn 找到设备

                            if(!$mysql_device['owner_user']){//设备未认领

                                $response_data = [
                                    'command' => $client_data['command'],
                                    'ret' => "0",
                                    'code' => "10002",
                                    'msg' => "Equipment not certified"
                                ];
                            
                                sendData($server,$fd,$response_data);

                            }else{//设备已认领

                                //更新MySQL数据
                                $mysql_device_data = [
                                    'on_status' => 0,
                                    'fd' => $fd,
                                ];
                                Db::connect('mysql')->table('dev_device')->where('id',$client_data['gsn'])->update($mysql_device_data);


                                $token = setToken();
               
                                $mongo_device_data = [
                                    'on_status' => 0, 
                                    'token' => $token, 
                                    'last_gps' => $client_data['last_gps'] ?? "", 
                                    'last_ip'=>$clientInfo['remote_ip'], 
                                    'fd'=>$fd
                                ];

                                Db::table('dev_sn')->where('gsn',$client_data['gsn'])->update($mongo_device_data);

                                $response_data = [];
                                $response_data['command'] = $client_data['command'];
                                $response_data['ret'] = "0";
                                $response_data['token'] = $token;
                                $response_data['code'] = "10000";
                            
                                sendData($server,$fd,$response_data);

                            }

                        }
                           
                    }

                }
            
            }else if($client_data['command'] == "02"){//设备下线

                $mongodb_device = Db::table('dev_sn')->where('token',$client_data['token'])->find();

                if(!$mongodb_device){

                    $response_data = [
                        'command' => $client_data['command'],
                        'ret' => "0",
                        'code' => "10006",
                        'msg' => "Incorrect token",
                    ];
                
                }else{

                    $response_data = [
                        'command' => $client_data['command'],
                        'ret' => "0",
                        'code' => "10000"
                    ];

                }

                sendData($server,$fd,$response_data);

                $server->close($fd);

            }else if($client_data['command'] == "03"){//设备心跳

                $mongodb_device = Db::table('dev_sn')->where('token',$client_data['token'])->find();

                if(!$mongodb_device){//如果未找到设备关闭连接

                    $response_data = [
                        'command' => $client_data['command'],
                        'ret' => "0",
                        'code' => "10006",
                        'msg' => "Incorrect token",
                    ];
                    sendData($server,$fd,$response_data);
                    $server->close($fd);
                
                }else{

                    $response_data = [
                        'command' => $client_data['command'],
                        'ret' => "0",
                        'code' => "10000"
                    ];
                    sendData($server,$fd,$response_data);
                }

            }
        }
        if(array_key_exists("response",$client_data)){//向设备发送操作指令设备返回

            $bus_log_data = [
                'response' => $data,
                'response_date' => date('Y-m-d H:i:s'),
                'response_time' => time(),
            ];

            // 写入设备日志
            Db::table('bus_log')->where('id',$client_data['id'])->update($bus_log_data);

        }
        if(array_key_exists('business',$client_data)){//后台管理连接

            //添加业务系统日志
            $bus_log_data = [
                'gsn' => $client_data['gsn'],
                'id' => $client_data['id'] ?? "",
                'get' => $data,
                'get_date' => date('Y-m-d H:i:s'),
                'get_time' => time(),
                'send' => "",
                'send_date' => "",
                'send_time' => "",
                'response' => "",
                'response_date' => "",
                'response_time' => "",
            ];
            Db::table('bus_log')->insert($bus_log_data);

            $mongodb_device = Db::table('dev_sn')->where('gsn',$client_data['gsn'])->find();

            if($mongodb_device['on_status'] == 0 && $mongodb_device['fd']){//如果设备在线，发送指令

                $client_data['params']['id'] = $client_data['id'];
                $bus_log_data = [
                    'send' => json_encode($client_data['params']),
                    'send_date' => date('Y-m-d H:i:s'),
                    'send_time' => time(),
                ];
                
                Db::table('bus_log')->where('id',$client_data['id'])->update($bus_log_data);

                try{

                    sendData($server,$mongodb_device['fd'],$client_data['params']);//向设备发送指令

                    $response_data = [
                        'business' => $client_data['business'],
                        'ret' => "0",
                        'code' => "10000",
                        'msg' => "success",
                    ];
                    
                    sendData($server,$fd,$response_data);

                }catch(\Exception $e){

                    $response_data = [
                        'business' => $client_data['business'],
                        'ret' => "0",
                        'code' => "10006",
                        'msg' => "failed",
                    ];
                   
                    sendData($server,$fd,$response_data);

                }


            }else{//如果设备不在线

                if($client_data['online'] == 0){//如果指令离线时不需要缓存，直接拒绝

                    $response_data = [
                        'business' => $client_data['business'],
                        'ret' => "0",
                        'code' => "10005",
                        'msg' => "device not online",
                    ];
                    sendData($server,$fd,$response_data);

                }else if($client_data['online'] == 1){//如果指令离线时需要缓存，将指令保存到mongodb表

                    

                    $dev_queue_data = [
                        'command_name' => $client_data['business'], 
                        'command_collents' => $client_data['params'],
                        'gsn' => $client_data['gsn'],
                        'id' => '{"$ref":"dev_sn","$id":"'.$mongodb_device->_id.'","$db":"device"}', 
                        'status'=>0, 
                        'publish_time'=>time(), 
                        'subscribe'=>0
                    ];

                    logs('swoole_tcp')->info(json_encode($dev_queue_data));

                    Db::table('dev_queue')->insert($dev_queue_data);

                    $response_data = [
                        'business' => $client_data['business'],
                        'ret' => "0",
                        'code' => "10000",
                        'msg' => "Command cached",
                    ];

                    sendData($server,$fd,$response_data);

                }
            } 

        }

    }


});

//监听连接关闭事件
$server->on('Close', function ($server, $fd) {

    //更新MySQL数据
    $data = [
        'on_status' => 1, 
        'fd' => 0,
    ];
    Db::connect('mysql')->table('dev_device')->where('fd',$fd)->update($data);

    //更新mongodb数据
    Db::table('dev_sn')->where('fd',$fd)->update($data);

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