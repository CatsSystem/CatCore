<?php
/**
 * Created by PhpStorm.
 * User: lidanyang
 * Date: 16/10/26
 * Time: 下午6:53
 */

namespace core\component\client;

use core\common\Error;
use core\common\Globals;
use core\concurrent\Promise;

/**
 * 异步Http2客户端封装
 * Class Http2
 * @package core\component\client
 */
class Http2
{
    /**
     * @var \swoole_http2_client
     */
    protected $http_client;

    /**
     * 待请求的域名
     * @var string
     */
    private $domain;

    /**
     * 是否是https请求
     * @var bool
     */
    private $is_ssl;

    /**
     * 端口号
     * @var int
     */
    private $port;

    /**
     * @var int 错误码
     */
    public $errno;

    /**
     * @var string 错误信息
     */
    public $error;

    private $closed = true;

    /**
     * Http constructor.
     * @param string $domain 域名(不带http前缀)或者IP
     * @param bool $is_ssl 是否开启SSL (https)
     * @param int $port 端口号,默认80, https默认为443
     * @throws \Exception
     */
    public function __construct($domain, $is_ssl = false, $port = 80)
    {
        if(!Globals::isWorker())
        {
            throw new \Exception("Use CURL in Task Worker!");
        }
        $this->domain = $domain;
        $this->is_ssl = $is_ssl;

        if( $is_ssl && $port = 80 ) {
            $port = 443;
        }
        $this->port = $port;
    }

    /**
     * 初始化http客户端
     * @return Promise
     * @throws \Exception
     */
    public function init()
    {
        $promise = new Promise();

        // 利用ip2long方法检测传入的是否为IP
        if( !ip2long($this->domain) ) {   // 传入的是域名
            swoole_async_dns_lookup($this->domain, function ($host, $ip) use ($promise){
                $this->domain = $ip;
                $this->http_client = new \swoole_http2_client($this->domain, $this->port, $this->is_ssl);
                $promise->resolve(Error::SUCCESS);
            });

        } else {
            $this->http_client = new \swoole_http2_client($this->domain, $this->port, $this->is_ssl);
            $promise->resolve(Error::SUCCESS);
        }
        $this->closed = false;
        $this->http_client->on("Close", function(){
            $this->closed = true;
        });
        return $promise;
    }

    /**
     * @param string    $path
     * @param int       $timeout
     * @return Promise
     */
    public function get($path, $timeout = 3000)
    {
        $promise = new Promise();
        if($this->closed)
        {
            $promise->resolve([
                'code'  => Error::ERR_HTTP_CONN_CLOSE
            ]);
            return $promise;
        }
        $timeId = swoole_timer_after($timeout, function() use ($promise){
            $this->http_client->close();
            $promise->resolve([
                'code'  => Error::ERR_HTTP_TIMEOUT
            ]);
        });
        $this->http_client->get($path, function($cli) use($promise,$timeId){
            \swoole_timer_clear($timeId);
            $this->http_client->close();
            $promise->resolve([
                'code'      => Error::SUCCESS,
                'data'      => $cli->body,
                'status'    => $cli->statusCode
            ]);
        });
        return $promise;
    }

    /**
     * @param string    $path
     * @param string    $data
     * @param int       $timeout
     * @return Promise
     */
    public function post($path, $data, $timeout = 3000)
    {
        $promise = new Promise();
        if($this->closed)
        {
            $promise->resolve([
                'code'  => Error::ERR_HTTP_CONN_CLOSE
            ]);
            return $promise;
        }
        $timeId = swoole_timer_after($timeout, function() use ($promise){
            $this->http_client->close();
            $promise->resolve([
                'code'  => Error::ERR_HTTP_TIMEOUT
            ]);
        });

        $this->http_client->post($path, $data, function($cli) use($promise,$timeId){
            \swoole_timer_clear($timeId);
            $this->http_client->close();
            $promise->resolve([
                'code'      => Error::SUCCESS,
                'data'      => $cli->body,
                'status'    => $cli->statusCode
            ]);
        });
        return $promise;
    }

    public function openStream($path, $callback)
    {
        if($this->closed)
        {
            return Error::ERR_HTTP_CONN_CLOSE;
        }
        return $this->http_client->openStream($path, $callback);
    }

    public function push($stream_id, $data)
    {
        if($this->closed)
        {
            return Error::ERR_HTTP_CONN_CLOSE;
        }
        $this->http_client->push($stream_id, $data);
        return Error::SUCCESS;
    }

    public function closeStream($stream_id)
    {
        if($this->closed)
        {
            return;
        }
        $this->http_client->closeStream($stream_id);
    }

    public function cookie()
    {
        return $this->http_client->cookies;
    }

    public function header()
    {
        return $this->http_client->headers;
    }

    public function close()
    {
        $this->http_client->close();
    }

    public function setHeaders(array $headers)
    {
        $this->http_client->setHeaders($headers);
    }

    public function setCookies(array $cookies)
    {
        $this->http_client->setCookies($cookies);
    }
}