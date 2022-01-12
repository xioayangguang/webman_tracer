<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaoxiao
 */

namespace Xiaoyangguang\WebmanTracer\Bootstrap;

use Webman\Bootstrap;
use Webman\Middleware;
use Xiaoyangguang\WebmanAop\Bootstrap\AopRegister;
use Xiaoyangguang\WebmanTracer\Core\Injection;
use Xiaoyangguang\WebmanTracer\Core\TracerInitialize;

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
            //$tracer[Injection::class] = [Middleware::class => ['getMiddleware']];
            AopRegister::appendProxy($tracer);
        }
    }
}