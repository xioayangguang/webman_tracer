<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaoxiao
 */

namespace xioayangguang\webman_tracer\example;

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
        SpanManage::startNextSpan("Elasticsearch::{$class}::{$method}", function (Span $child_span) use ($params) {
            foreach ($params as $key => $value) {
                $child_span->tag($key, json_encode($value));
            }
            $child_span->setRemoteEndpoint(Endpoint::create('Elasticsearch', '127.0.0.4', null, null));
        });
    }
}