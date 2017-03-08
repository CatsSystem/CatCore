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
 * 异步Http客户端封装
 * Class Http
 * @package core\component\client
 */
class Http
{
    /**
     * @var \swoole_http_client
     */
    private $http_client;

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
                $this->http_client = new \swoole_http_client($this->domain, $this->port, $this->is_ssl);
                $promise->resolve(Error::SUCCESS);
            });
            return $promise;
        } else {
            $this->http_client = new \swoole_http_client($this->domain, $this->port, $this->is_ssl);
            $promise->resolve(Error::SUCCESS);
            return $promise;
        }
    }

    /**
     * @param string    $path
     * @param int       $timeout
     * @return Promise
     */
    public function get($path, $timeout = 3000)
    {
        $promise = new Promise();
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

    /**
     * @param string    $path
     * @param int       $timeout
     * @return Promise
     */
    public function execute($path, $timeout = 3000)
    {
        $promise = new Promise();
        $timeId = swoole_timer_after($timeout, function() use ($promise){
            $this->http_client->close();
            $promise->resolve([
                'code'  => Error::ERR_HTTP_TIMEOUT
            ]);
        });
        $this->http_client->execute($path, function($cli) use($promise,$timeId){
            \swoole_timer_clear($timeId);
            $promise->resolve([
                'code'      => Error::SUCCESS,
                'data'      => $cli->body,
                'status'    => $cli->statusCode
            ]);
        });
        return $promise;
    }

    public function cookie()
    {
        return $this->http_client->cookies;
    }

    public function close()
    {
        $this->http_client->close();
    }

    public function __call($name, $arguments)
    {
        if($name == 'get' || $name == 'post' || $name == 'execute' ) {
            return false;
        }
        return call_user_func_array([$this->http_client, $name], $arguments);
    }
}