<?php
//区分大小写
use support\Redis;
use Xiaoyangguang\WebmanTracer\Core\TracerInitialize;
use Xiaoyangguang\WebmanTracer\Example\ElasticsearchAspect;
use Xiaoyangguang\WebmanTracer\Example\GenericAspect;
use Xiaoyangguang\WebmanTracer\Example\MysqlAspect;
use Xiaoyangguang\WebmanTracer\Example\RedisAspect;

//TracerInitialize::setConfig(true);
MysqlAspect::setConfig('业务数据库', '127.0.0.1');
RedisAspect::setConfig('业务Redis');
ElasticsearchAspect::setConfig('业务Elasticsearch');
//HttpAspect::setConfig();

//下面自定义
return [
    RedisAspect::class => [ //追踪类
        Redis::class => [  //被追踪类
            '__callStatic', //被追踪方法
        ],
    ],
    GenericAspect::class => [ //追踪类 通用追踪节点 任由开发者发挥
        \app\controller\Index::class => [
            'json',
        ],
    ],
];
