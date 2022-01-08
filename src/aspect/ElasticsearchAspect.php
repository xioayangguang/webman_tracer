<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaoxiao
 */

namespace xioayangguang\webman_tracer\aspect;

use xioayangguang\webman_tracer\SpanManage;
use Zipkin\Endpoint;
use Zipkin\Span;

class ElasticsearchAspect extends GenericAspect
{
    /**
     * 前置通知
     * @param $params
     * @param $class
     * @param $method
     */
    public static function beforeAdvice($params, $class, $method): void
    {
        SpanManage::startNextSpan("es::{$class}::{$method}", function (Span $child_Span) use ($params) {
            foreach ($params as $key => $value) {
                $child_Span->tag($key, json_encode($value));
            }
            $child_Span->setRemoteEndpoint(Endpoint::create('Elasticsearch', '127.0.0.4', null, null));
        });
    }
}