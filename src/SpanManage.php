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
            /** @var Span $parent_span */
            $parent_span = end(self::$span_stack);
            $child_span = self::$tracer->nextSpan($parent_span->getContext());
            $child_span->setKind(CLIENT);
            $child_span->setName($span_name);
            $child_span->start();
            //用 self::$span_stack[] = $child_span;  比array_push效率更高
            //array_push(self::$span_stack, $child_span);
            self::$span_stack[] = $child_span;
            if (is_callable($callable)) $callable($child_span);
            return $child_span;
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
            /** @var Span $child_span */
            $child_span = end(self::$span_stack);
            if ($callable) $callable($child_span);
            $child_span->finish();
            array_pop(self::$span_stack);
        }
    }

    /**
     * 创建 root span
     * @param callable $before_callable
     * @param callable $after_callable
     * @param array|null $carrier
     * @return Response
     * @throws \Throwable
     */
    public static function startRootSpan(callable $before_callable, callable $after_callable, array $carrier = null)
    {
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
            $response = $before_callable($root_span);
            if (is_callable($after_callable)) $after_callable($root_span, $response);
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
     * @throws \Exception
     */
    public static function createTracer()
    {
        if (!self::$tracing instanceof Tracing) {
            $tracer = config('tracer');
            if (empty($tracer['endpoint_url'])) new \Exception('endpoint_url 不能为空');
            $ipv4 = empty($tracer['ipv4']) ? self::getServerIp() : $tracer['ipv4'];
            $service_name = empty($tracer['service_name']) ? 'API_SERVICE' : $tracer['service_name'];
            $endpoint = Endpoint::create($service_name, $ipv4, null, $tracer['port'] ?? null);
            $reporter = new Http(['endpoint_url' => $tracer['endpoint_url']]);
            $sampler = PercentageSampler::create($tracer['rate'] ?? 1);
            self::$tracing = TracingBuilder::create()
                ->havingLocalEndpoint($endpoint)
                ->havingSampler($sampler)
                ->havingReporter($reporter)
                ->build();
            self::$tracer = self::$tracing->getTracer();
            Timer::add($tracer['report_time'] ?? 10, function () {
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
                $tmp_array = explode(":", $out[1]);
                $tmp_ip = explode(" ", $tmp_array[1]);
                if (preg_match($preg, trim($tmp_ip[0]))) {
                    return trim($tmp_ip[0]);
                }
            }
        }
        return '127.0.0.1';
    }
}
