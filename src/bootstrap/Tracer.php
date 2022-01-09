<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaoxiao
 */

namespace xioayangguang\webman_tracer\bootstrap;

use Webman\Bootstrap;
use Webman\Middleware;
use xioayangguang\webman_aop\bootstrap\AopRegister;
use xioayangguang\webman_tracer\core\Injection;
use xioayangguang\webman_tracer\SpanManage;

class Tracer implements Bootstrap
{
    /**
     * @param \Workerman\Worker $worker
     * @return mixed|void
     * @throws \Exception
     */
    public static function start($worker)
    {
        if (config('tracer.is_enable', false)) {
            $tracer = config('tracer.tracer');
            $tracer[Injection::class] = [Middleware::class => ['getMiddleware']];
            AopRegister::appendProxy($tracer);
            SpanManage::createTracer();
            //Bootstrap 加载的时候 APP没加载 此方法行不通
            //Middleware::load(['' => [\xioayangguang\webman_tracer\middleware\Tracer::class]]);
        }
    }
}