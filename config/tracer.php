<?php
//区分大小写
use app\social\service\PostService;
use support\bootstrap\EsClient;
use support\Redis;
use think\db\PDOConnection;
use xioayangguang\webman_tracer\core\TracerInitialize;
use xioayangguang\webman_tracer\example\ElasticsearchAspect;
use xioayangguang\webman_tracer\example\GenericAspect;
use xioayangguang\webman_tracer\example\MysqlAspect;
use xioayangguang\webman_tracer\example\RedisAspect;

TracerInitialize::setConfig(true);
MysqlAspect::setConfig('业务数据库', '127.0.0.1');
RedisAspect::setConfig('业务Redis');
ElasticsearchAspect::setConfig('业务Elasticsearch');
//HttpAspect::setConfig();

return [
    RedisAspect::class => [ //追踪类
       Redis::class => [  //被追踪类
            '__callStatic', //被追踪方法
        ],
    ],
    ElasticsearchAspect::class => [//追踪类
        EsClient::class => [
            '__callStatic',//被追踪方法
            '__call',//被追踪方法
        ],
    ],
    MysqlAspect::class => [//追踪类
        PDOConnection::class => [  //追踪底层数据库执行方法例子
            'getPDOStatement',//被追踪方法
        ],
    ],
    GenericAspect::class => [ //追踪类 通用追踪节点 任由开发者发挥
        PostService::class => [
            'searchByIds',
        ],
    ],
];
