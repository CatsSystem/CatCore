<?php
/**
 * Created by PhpStorm.
 * User: lidanyang
 * Date: 17/3/13
 * Time: 15:07
 */

namespace core\auth;

use core\common\Error;
use core\component\client\Redis;

/**
 * 鉴权类
 * Class Auth
 * @package core\auth
 */
class Auth
{
    private static $instance = null;
    
    /**
     * @return Auth
     */
    public static function getInstance()
    {
        if(Auth::$instance == null)
        {
            Auth::$instance = new Auth();
        }
        return Auth::$instance;
    }
    
    protected function __construct()
    {
    
    }

    /**
     * @var Redis
     */
    private $redis;

    /**
     * @var string
     */
    private $prefix;

    /**
     * 初始化鉴权类
     * @param $prefix       string      存储使用的key前缀
     * @param $redis        Redis      Redis配置, 用于存储生成的access key
     * @return \Generator
     */
    public function init($prefix, $redis)
    {
        $this->prefix = $prefix;
        $this->redis = $redis;
    }

    /**
     * 获取AccessToken
     * @param $uuid     string      标示ID
     * @param $secret   string      秘钥
     * @param $expire   int         超时时间, 单位s
     * @return string access_token
     */
    public function grantAccessToken($uuid, $secret, $expire)
    {
        $access_token = AccessToken::getAccessToken($uuid, $secret);
        if(empty($this->redis))
        {
            return null;
        }
        $result = yield $this->redis->set($this->prefix . $uuid, $access_token);
        if($result['code'] == Error::SUCCESS) {
            $this->redis->expire($this->prefix . $uuid, $expire);
            return $access_token;
        }
        return null;
    }

    /**
     * 检查AccessToken
     * @param $uuid         string      标示ID
     * @param $token        string      access token
     * @return bool                     是否检测成功
     */
    public function checkAccessToken($uuid, $token)
    {
        $result = yield $this->redis->get($this->prefix . $uuid);
        if($result['code'] != Error::SUCCESS) {
            return false;
        }
        return $result['data'] === $token;
    }
}