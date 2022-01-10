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
use xioayangguang\webman_tracer\core\TracerInitialize;

class Tracer implements Bootstrap
{
    /**
     * @param \Workerman\Worker $worker
     * @return mixed|void
     * @throws \Exception
     */
    public static function start($worker)
    {
        if (TracerInitialize::createTracer()) {
            $tracer = config('tracer');
            $tracer[Injection::class] = [Middleware::class => ['getMiddleware']];
            AopRegister::appendProxy($tracer);
        }
    }
}