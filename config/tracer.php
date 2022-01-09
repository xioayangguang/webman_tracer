<?php
//严格区分大小写
use xioayangguang\webman_tracer\example\ElasticsearchAspect;
use xioayangguang\webman_tracer\example\GenericAspect;
use xioayangguang\webman_tracer\example\MysqlAspect;
use xioayangguang\webman_tracer\example\RedisAspect;
use app\social\service\PostService;
use support\Redis;
use support\EsClient;
use think\db\PDOConnection;

return [
    'is_enable' => true,  // 是否开启 可空默认false
    'rate' => 0.99,  // 抽样率 0到1之间 可空默认为1
    'report_time' => 10,  //每10秒上报一次  可空默认10秒
    'service_name' => 'API_SERVICE', //当前节点名称可空
    'ipv4' => '',  //ip地址可空
    'port' => '8787', //端口可空
    'endpoint_url' => 'http://127.0.0.1:9411/api/v2/spans', //上报地址
    'tracer' => [
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
    ]
];



