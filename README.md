### webmanTracer使用教程

> webman的链路追踪组件，基于xiaoyangguang/webman_aop 实现了基本的链路追踪组件，
> 比如mysql es redis 开发者可自定义追踪方法函数，实现自己需要追踪的组件，实现自定义追踪
> ，也可追踪composer加载的组件，比如thinkorm的数据库执行函数

#### 安装

```
composer require xiaoyangguang/webman_tracer
```

> 配置 middleware.php.php文件

```php
<?php
return [
    xioayangguang\webman_tracer\middleware\Tracer::class 
     //....省略其他 
];
```

> 我们需要在 config 目录下，增加 tracer.php 配置

```php
<?php
//区分大小写
use xioayangguang\webman_tracer\aspect\ElasticsearchAspect;
use xioayangguang\webman_tracer\aspect\GenericAspect;
use xioayangguang\webman_tracer\aspect\MysqlAspect;
use xioayangguang\webman_tracer\aspect\RedisAspect;

return [
    'rate' => 0.99,  // 抽样率 0到1之间 可空默认为1
    'report_time' => 10,  //每10秒上报一次  可空默认10秒
    'service_name' => 'API_SERVICE', //当前节点名称可空
    'ipv4' => '', // ip 地址可空
    'port' => '8787', //端口可空
    'endpoint_url' => 'http://127.0.0.1:9411/api/v2/spans', //上报地址
    'tracer' => [
        RedisAspect::class => [ //追踪类
            \support\Redis::class => [  //被追踪类
                '__callStatic', //被追踪方法
            ],
        ],
        ElasticsearchAspect::class => [//追踪类
            \support\EsClient::class => [
                '__callStatic',//被追踪方法
                '__call',//被追踪方法
            ],
        ],
        MysqlAspect::class => [//追踪类
            'vendor/topthink/think-orm/src/db/PDOConnection' => [  //追踪底层数据库执行方法例子
                'getPDOStatement',//被追踪方法
            ],
        ],
        GenericAspect::class => [ //追踪类 通用追踪节点 任由开发者发挥
            app\social\service\PostService::class => [
                'searchByIds',
            ],
        ],
    ]
];
```

> 自定义追踪切面 并配置在tracer.php 中(可选)

```php
<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaoxiao
 */

namespace xioayangguang\webman_tracer\aspect;

use xioayangguang\webman_aop\AspectInterface;
use xioayangguang\webman_tracer\SpanManage;
use Zipkin\Span;

class GenericAspect implements AspectInterface
{
    /**
     * 添加调用参数到追踪组件
     * @param $params
     * @param $class
     * @param $method
     */
    public static function beforeAdvice($params, $class, $method): void
    {
        //SpanManage自动维护调用栈 parentspan
        SpanManage::startNextSpan($class . '::' . $method, function (Span $child_Span) use ($params) {
            foreach ($params as $key => $value) {
                $child_Span->tag("params_{$key}", json_encode($value));
            }
        });
    }

    /**
     * 函数执行完成后收集数据到追踪组件中
     * @param $res
     * @param $params
     * @param $class
     * @param $method
     */
    public static function afterAdvice(&$res, $params, $class, $method): void
    {
       //SpanManage自动维护调用栈 parentspan  有startNextSpan 必须有stopNextSpan 
        SpanManage::stopNextSpan(function (Span $child_Span) use ($params, $res) {
            $child_Span->tag('method_return', json_encode($res));
        });
    }

    /**
     * 处理上面异常情况，或者业务异常情况，清理数据并上报
     * @param $throwable
     * @param $params
     * @param $class
     * @param $method
     */
    public static function exceptionHandler($throwable, $params, $class, $method): void
    {
        SpanManage::stopNextSpan(function (Span $child_Span) use ($throwable) {
            $child_Span->tag('exception.message', $throwable->getMessage());
            $child_Span->tag('exception.code', $throwable->getCode());
            $child_Span->tag('exception.stacktrace', $throwable->getTraceAsString());
        });
    }
}
```

> 最后启动服务，并测试。

```shell
php start.php start
curl  http://127.0.0.1:8787
查看平台数据
```


