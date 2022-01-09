<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaoxiao
 */

namespace xioayangguang\webman_tracer\core;

use Webman\App;
use xioayangguang\webman_aop\AspectInterface;

class Injection implements AspectInterface
{
    public static function beforeAdvice($params, $class, $method): void
    {
    }
    public static function afterAdvice(&$res, $params, $class, $method): void
    {
        array_push($res, [App::container()->get(RootSpan::class), 'process']);
    }
    public static function exceptionHandler($throwable, $params, $class, $method): void
    {
    }
}