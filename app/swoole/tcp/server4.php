<?php

//创建Server对象，监听 127.0.0.1:9501 端口
$server = new Swoole\Server('0.0.0.0', 9505);

// $server->set(array(

//     // 'open_length_check' => true,    //打开包长检测特性
//     // 'package_max_length' => 64,  //设置最大数据包尺寸，单位为字节
//     // 'package_length_type' => $packModel, //see php pack() 长度值的类型，接受一个字符参数，与 PHP 的 pack 函数一致
//     // 'package_length_offset' => 0, //length 长度值在包头的第几个字节
//     // 'package_body_offset' => 0, //从第几个字节开始计算长度，一般有 2 种情况

// ));

//监听连接进入事件
$server->on('Connect', function ($server, $fd) {
    echo "Client: Connect.\n";
});

//监听数据接收事件
$server->on('Receive', function ($server, $fd, $reactor_id, $data) {
    echo "{$data}\n";
    $server->send($fd, "Server: {$data}");
});

//监听连接关闭事件
$server->on('Close', function ($server, $fd) {
    echo "Client: Close.\n";
});

//启动服务器
$server->start(); 
