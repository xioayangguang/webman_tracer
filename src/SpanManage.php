<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaoxiao
 */

namespace xioayangguang\webman_tracer;

use Webman\Http\Response;
use Workerman\Timer;
use Zipkin\Endpoint;
use Zipkin\Propagation\DefaultSamplingFlags;
use Zipkin\Propagation\Map;
use Zipkin\Reporters\Http;
use Zipkin\Samplers\PercentageSampler;
use Zipkin\Span;
use Zipkin\Tracer;
use Zipkin\Tracing;
use Zipkin\TracingBuilder;
use const Zipkin\Kind\CLIENT;
use const Zipkin\Kind\SERVER;

class SpanManage
{
    /**
     * @var array
     */
    private static $span_stack = [];
    /**
     * @var Tracing
     */
    private static $tracing = null;

    /**
     * @var Tracer
     */
    private static $tracer = null;

    /**
     * @var bool
     */
    private static $initialization = false;

    /**
     * 创建子span（必须和stopNextSpan一一对应）
     * @param string $span_name
     * @param callable|null $callable
     * @return void|\Zipkin\Span
     */
    public static function startNextSpan(string $span_name, callable $callable = null)
    {
        if (self::$initialization) {
            /** @var Span $parentContext */
            $parentContext = end(self::$span_stack);
            $child_Span = self::$tracer->nextSpan($parentContext->getContext());
            $child_Span->setKind(CLIENT);
            $child_Span->setName($span_name);
            $child_Span->start();
            //用 self::$span_stack[] = $child_Span;  比array_push效率更高
            //array_push(self::$span_stack, $child_Span);
            self::$span_stack[] = $child_Span;
            if (is_callable($callable)) $callable($child_Span);
            return $child_Span;
        }
    }

    /**
     * 停止子span
     * @param callable|null $callable
     */
    public static function stopNextSpan(callable $callable = null)
    {
        if (self::$initialization) {
            //不在此pop的原因是后置操作如有异常栈会异常
            /** @var Span $child_Span */
            $child_Span = end(self::$span_stack);
            if ($callable) $callable($child_Span);
            $child_Span->finish();
            array_pop(self::$span_stack);
        }
    }

    /**
     * 创建 root span
     * @param callable $beforeCallable
     * @param callable $afterCallable
     * @param array|null $carrier
     * @return Response
     * @throws \Throwable
     */
    public static function startRootSpan(callable $beforeCallable, callable $afterCallable, array $carrier = null)
    {
        self::createTracer();
        if (isset($carrier['x-b3-traceid']) and isset($carrier['x-b3-spanid']) and
            isset($carrier['x-b3-parentspanid']) and isset($carrier['x-b3-sampled'])
        ) {
            $extractor = self::$tracing->getPropagation()->getExtractor(new Map());
            $root_span = self::$tracer->nextSpan($extractor($carrier));
        } else {
            $root_span = self::$tracer->newTrace(DefaultSamplingFlags::createAsEmpty());
        }
        $root_span->setKind(SERVER);
        self::$span_stack = [];
        self::$initialization = true;
        $root_span->start();
        try {
            array_push(self::$span_stack, $root_span);
            $response = $beforeCallable($root_span);
            if (is_callable($afterCallable)) $afterCallable($root_span, $response);
            return $response;
        } catch (\Throwable $throwable) {
            $root_span->tag('method.message', $throwable->getMessage());
            $root_span->tag('method.code', $throwable->getCode());
            $root_span->tag('method.stacktrace', $throwable->getTraceAsString());
            throw $throwable;
        } finally {
            array_pop(self::$span_stack);
            self::$initialization = false;
            $root_span->finish();
            self::$tracer->flush();
        }
    }

    /**
     * 初始化链路追踪
     */
    private static function createTracer()
    {
        if (!self::$tracing instanceof Tracing) {
            var_dump('初始化一次');
            $endpoint = Endpoint::create('API服务', self::getServerIp(), null, 8787);
            $reporter = new Http(['endpoint_url' => 'http://127.0.0.1:9411/api/v2/spans']);
            $sampler = PercentageSampler::create(0.99);
            self::$tracing = TracingBuilder::create()
                ->havingLocalEndpoint($endpoint)
                ->havingSampler($sampler)
                ->havingReporter($reporter)
                ->build();
            self::$tracer = self::$tracing->getTracer();
            Timer::add(10, function () {
                self::$tracer->flush();
            });
            register_shutdown_function(function () {
                self::$tracer->flush();
            });
        }
    }

    /**
     * 获取服务器局域网ip
     * @return mixed
     */
    private static function getServerIp()
    {
        $preg = "/\A((([0-9]?[0-9])|(1[0-9]{2})|(2[0-4][0-9])|(25[0-5]))\.){3}(([0-9]?[0-9])|(1[0-9]{2})|(2[0-4][0-9])|(25[0-5]))\Z/";
        exec("ifconfig", $out, $stats);
        if (!empty($out)) {
            if (isset($out[1]) && strstr($out[1], 'addr:')) {
                $tmpArray = explode(":", $out[1]);
                $tmpIp = explode(" ", $tmpArray[1]);
                if (preg_match($preg, trim($tmpIp[0]))) {
                    return trim($tmpIp[0]);
                }
            }
        }
        return '127.0.0.1';
    }
}
