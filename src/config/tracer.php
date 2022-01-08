<?php
//定义切入方法区分大小写
return [
    'rate' => 0.99,
    'report_time' => 10,
    'endpoint_url' => 'http://127.0.0.1:9411/api/v2/spans',

    \app\aop\RedisAspect::class => [
        \support\bootstrap\Redis::class => [
            '__callStatic',
        ],
    ],
    \app\aop\ESAspect::class => [
        \support\bootstrap\EsClient::class => [
            '__callStatic',
            '__call',
        ],
    ],
    \app\aop\MysqlAspect::class => [
        'vendor/topthink/think-orm/src/db/PDOConnection' => [  //切入底层数据库执行方法
            'getPDOStatement',
        ],
    ],

    \app\aop\MethodAspect::class => [
        \app\aop\Test::class => [
            'search',
        ],
        app\social\service\PostService::class => [
            'searchByIds',
        ],
    ],
];



