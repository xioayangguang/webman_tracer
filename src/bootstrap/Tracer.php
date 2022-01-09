<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaoxiao
 */

namespace xioayangguang\webman_tracer\bootstrap;

use Webman\Bootstrap;
use Webman\Middleware;
use xioayangguang\webman_aop\bootstrap\AopRegister;
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
            AopRegister::appendProxy(config('tracer.tracer'));
            AopRegister::autoloadRegister();
            SpanManage::createTracer();
            Middleware::load(['' => [\xioayangguang\webman_tracer\middleware\Tracer::class]]);
        }
    }
}