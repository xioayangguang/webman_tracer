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
     * 前置通知
     * @param $params
     * @param $class
     * @param $method
     */
    public static function beforeAdvice($params, $class, $method): void
    {
        SpanManage::startNextSpan($class . '::' . $method, function (Span $child_Span) use ($params) {
            foreach ($params as $key => $value) {
                $child_Span->tag("params_{$key}", json_encode($value));
            }
        });
    }

    /**
     * 后置通知
     * @param $res
     * @param $params
     * @param $class
     * @param $method
     */
    public static function afterAdvice(&$res, $params, $class, $method): void
    {
        SpanManage::stopNextSpan(function (Span $child_Span) use ($params, $res) {
            $child_Span->tag('method_return', json_encode($res));
        });
    }

    /**
     * 异常处理
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