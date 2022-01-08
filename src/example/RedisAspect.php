<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaoxiao
 */

namespace xioayangguang\webman_tracer\example;

use xioayangguang\webman_tracer\aspect\GenericAspect;
use xioayangguang\webman_tracer\SpanManage;
use Zipkin\Endpoint;
use Zipkin\Span;

class RedisAspect extends GenericAspect
{
    /**
     * 前置通知
     * @param $params
     * @param $class
     * @param $method
     */
    public static function beforeAdvice($params, $class, $method): void
    {
        SpanManage::startNextSpan("redis::{$class}::{$method}", function (Span $child_Span) use ($params) {
            if (isset($params['name']) and isset($params['arguments'])) {
                $child_Span->tag($params['name'], $params['arguments']);
            }
            $child_Span->setRemoteEndpoint(Endpoint::create('Redis', '127.0.0.3', null, null));
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
            if (is_string($res)) $child_Span->tag('result', $res);
        });
    }
}