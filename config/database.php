<?php
return [
    'default'    =>    'mongo',
   
    'connections' => [

        'mysql'    =>    [
            // 数据库类型
            'type'        => 'mysql',
            // 服务器地址
            'hostname'    => 'rm-wz9k66550b02uq193xo.mysql.rds.aliyuncs.com',
            // 数据库名
            'database'    => 'device',
            // 数据库用户名
            'username'    => 'dbadmin',
            // 数据库密码
            'password'    => 'Root202106',
            // 数据库连接端口
            'hostport'    => 3306,

        ],
        // 更多的数据库配置信息
        'mongo' => [
            // 数据库类型
            'type'            => 'mongo',
            // 服务器地址
            'hostname'        => 'dds-wz988e3b090fd1741493-pub.mongodb.rds.aliyuncs.com',
            // 数据库名
            'database'        => 'device',
            // 用户名
            'username'        => 'root',
            // 密码
            'password'        => 'App202106',
            // 端口
            'hostport'        => 3717,

        ],

    ],

];