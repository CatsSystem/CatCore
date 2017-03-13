<?php
/**
 * Created by PhpStorm.
 * User: lidanyang
 * Date: 17/3/13
 * Time: 15:07
 */

namespace core\auth;

use core\common\Constants;
use core\common\Error;
use core\common\Globals;
use core\component\client\Redis;

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

    public function init($prefix, $config)
    {
        $this->prefix = $prefix;
        $this->redis = new Redis($config,
            Globals::isWorker() ? Constants::MODE_ASYNC : Constants::MODE_SYNC);
        return yield $this->redis->connect(0);
    }

    /**
     * 获取AccessToken
     * @param $uuid     string
     * @param $secret   string
     * @param $expire   int         超时时间, 单位s
     * @return string access_token
     */
    public function grantAccessToken($uuid, $secret, $expire)
    {
        $access_token = AccessToken::get_access_token($uuid, $secret);
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


    public function checkAccessToken($uuid, $token)
    {
        $result = yield $this->redis->get($this->prefix . $uuid);
        if($result['code'] != Error::SUCCESS) {
            return null;
        }
        return $result['data'] === $token;
    }
}