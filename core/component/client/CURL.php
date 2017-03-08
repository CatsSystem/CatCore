<?php
/**
 * Created by PhpStorm.
 * User: lidanyang
 * Date: 17/3/3
 * Time: 11:51
 */

namespace core\component\client;

use core\common\Error;
use core\concurrent\Promise;

/**
 * 同步Http客户端封装
 * Class CURL
 * @package core\component\client
 */
class CURL
{
    /**
     * @var \resource
     */
    private $curl;

    /**
     * 待请求的域名
     * @var string
     */
    private $domain;

    /**
     * URL前缀(http or https)
     * @var string
     */
    private $prefix;

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
     * @param string    $domain     域名(不带http前缀)或者IP
     * @param bool      $is_ssl     是否开启SSL (https)
     * @param int       $port       端口号,默认80, https默认为443
     */
    public function __construct($domain, $is_ssl = false, $port = 80)
    {
        $this->domain = $domain;
        $this->prefix = $is_ssl ? 'http://' : 'https://';

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
        $this->curl = curl_init();

        curl_setopt($this->curl, CURLOPT_DNS_USE_GLOBAL_CACHE, FALSE);
        curl_setopt($this->curl, CURLOPT_DNS_CACHE_TIMEOUT, 300);
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($this->curl, CURLOPT_FAILONERROR, TRUE);
        curl_setopt($this->curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($this->curl, CURLOPT_MAXREDIRS, 5);
        curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($this->curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($this->curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/535.11 (KHTML, like Gecko) Chrome/17.0.963.83 Safari/535.11');
        $promise->resolve(Error::SUCCESS);
        return $promise;
    }

    public function get($path, $timeout = 3000)
    {
        $promise = new Promise();
        curl_setopt($this->curl, CURLOPT_CONNECTTIMEOUT_MS, $timeout);
        curl_setopt($this->curl, CURLOPT_TIMEOUT_MS, $timeout);

        $path = $this->prefix . $this->domain . ':' . $this->port . $path;
        curl_setopt($this->curl, CURLOPT_URL, $path);

        $response	= curl_exec($this->curl);
        if ($response === false)
        {
            $this->error = curl_error($this->curl);
            $this->errno = curl_errno($this->curl);
        }
        @curl_close($this->curl);
        unset($this->curl);
        $promise->resolve($response);
        return $promise;
    }

    public function post($path, $data, $timeout = 3000)
    {
        $promise = new Promise();

        curl_setopt($this->curl, CURLOPT_CONNECTTIMEOUT_MS, $timeout);
        curl_setopt($this->curl, CURLOPT_TIMEOUT_MS, $timeout);
        curl_setopt($this->curl, CURLOPT_POST, 1);
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, $data);

        $path = $this->prefix . $this->domain . ':' . $this->port . $path;
        curl_setopt($this->curl, CURLOPT_URL, $path);

        $response	= curl_exec($this->curl);
        if ($response === false)
        {
            $this->error = curl_error($this->curl);
            $this->errno = curl_errno($this->curl);
        }
        @curl_close($this->curl);
        unset($this->curl);
        $promise->resolve($response);
        return $promise;
    }
}