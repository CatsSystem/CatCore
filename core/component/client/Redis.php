<?php
/**
 * Created by PhpStorm.
 * User: lidanyang
 * Date: 17/3/3
 * Time: 10:52
 */

namespace core\component\client;

use core\common\Constants;
use core\common\Error;
use core\component\log\Log;
use core\concurrent\Promise;
use core\component\pool\BasePool;

/**
 * Redis连接对象的封装
 * Class Redis
 * @package core\component\client
 */
class Redis
{
    /**
     * 连接ID
     * @var int
     */
    public $id;

    /**
     * swoole_redis连接对象
     * @var \swoole_redis
     */
    private $db;

    /**
     * 超时时间
     * @var int
     */
    private $timeout = 3000;

    /**
     * 所属连接池
     * @var BasePool
     */
    private $pool;

    /**
     * 同步异步模式
     * @var int
     */
    private $mode;

    /**
     * 同步Redis连接对象
     * @var \Redis
     */
    private $link;

    /**
     * Redis constructor.
     * @param $config       array       配置选项
     * @param $mode         int         模式(<b>Constants</b>中的<b>MODE</b>常量)
     */
    public function __construct($config,  $mode = Constants::MODE_ASYNC)
    {
        $this->config = $config;
        $this->mode = $mode;
    }

    /**
     * 设置所属的连接池
     * @param $pool     BasePool    连接池对象
     */
    public function addPool($pool)
    {
        $this->pool = $pool;
    }

    private function inPool($close = false)
    {
        if( !empty($this->pool) )
        {
            $this->pool->push($this, $close);
        }
    }

    /**
     * 关闭Redis连接
     */
    public function close()
    {
        switch ($this->mode)
        {
            case Constants::MODE_ASYNC:
            {
                $this->db->close();
                unset($this->db);
                $this->inPool(true);
                break;
            }
            case Constants::MODE_SYNC:
            {
                $this->link->close();
                break;
            }
        }
    }

    /**
     * 建立Redis连接
     * @param $id           int     连接ID
     * @param $timeout      int     超时时间, 单位ms
     * @return Promise              Promise对象
     */
    public function connect($id, $timeout = 3000)
    {
        $this->id = $id;
        $promise = new Promise();
        switch ($this->mode)
        {
            case Constants::MODE_ASYNC:
            {
                $this->db = new \swoole_redis();

                $this->db->on("close", function(){
                    Log::INFO('MySQL', "Close connection {$this->id}");
                    $this->connect($this->id);
                });
                $timeId = swoole_timer_after($timeout, function() use ($promise){
                    $this->close();
                    $promise->resolve([
                        'code'  => Error::ERR_REDIS_TIMEOUT
                    ]);
                });
                $this->db->connect($this->config['host'], $this->config['port'],
                    function (\swoole_redis $client, $result) use($timeId,$promise){
                        \swoole_timer_clear($timeId);
                        if( $result === false ) {
                            $promise->resolve([
                                'code'      => Error::ERR_REDIS_CONNECT_FAILED,
                                'errCode'   => $client->errCode,
                                'errMsg'    => $client->errMsg,
                            ]);
                            return;
                        }
                        if( isset($this->config['pwd']) ) {
                            $client->auth($this->config['pwd'], function(\swoole_redis $client, $result) use ($promise){
                                if( $result === false ) {
                                    $this->close();
                                    $promise->resolve([
                                        'code'  => Error::ERR_REDIS_ERROR,
                                        'errCode'   => $client->errCode,
                                        'errMsg'    => $client->errMsg,
                                    ]);
                                    return;
                                }
                                $client->select($this->config['select'], function(\swoole_redis $client, $result){});
                                $promise->resolve([
                                    'code'  => Error::SUCCESS
                                ]);
                            });
                        } else {
                            $client->select($this->config['select'], function(\swoole_redis $client, $result){});
                            $promise->resolve([
                                'code'  => Error::SUCCESS
                            ]);
                        }
                    });
                break;
            }
            case Constants::MODE_SYNC:
            {
                $this->link = new \Redis();
                try {
                    $result = $this->link->connect($this->config['host'], $this->config['port'], $timeout);
                    if( !$result ) {
                        $promise->resolve([
                            'code'      => Error::ERR_REDIS_CONNECT_FAILED,
                            'errCode'   => -1,
                            'errMsg'    => $this->link->getLastError(),
                        ]);
                    }
                    if( isset($this->config['pwd']) ) {
                        $this->link->auth($this->config['pwd']);
                    }
                    $this->link->setOption(\Redis::OPT_SERIALIZER, \Redis::SERIALIZER_NONE);
                    $this->link->select($this->config['select']);
                    $promise->resolve([
                        'code'  => Error::SUCCESS
                    ]);
                }catch (\RedisException $e) {
                    $promise->resolve([
                        'code'      => Error::ERR_REDIS_CONNECT_FAILED,
                        'errCode'   => $e->getCode(),
                        'errMsg'    => $e->getMessage(),
                    ]);
                }

                break;
            }
        }

        return $promise;
    }

    /**
     * 调用Redis命令
     * @param $name         string      Redis命令
     * @param $arguments    array       Redis命令参数列表
     * @return Promise                  Promise对象
     */
    public function __call($name, $arguments)
    {
        $promise = new Promise();
        if( $name == 'subscribe' || $name == 'unsubscribe'
            || $name == 'psubscribe' || $name == 'punsubscribe' ) {
            $promise->resolve(null);
            return $promise;
        }
        switch ($this->mode)
        {
            case Constants::MODE_ASYNC:
            {
                $index = count($arguments);
                $timeId = swoole_timer_after($this->timeout, function() use ($promise){
                    $this->close();
                    $promise->resolve([
                        'code'  => Error::ERR_REDIS_TIMEOUT
                    ]);
                });
                $arguments[$index] = function (\swoole_redis $client, $result) use ($timeId, $promise){
                    \swoole_timer_clear($timeId);
                    if( $result === false )
                    {
                        $promise->resolve([
                            'code'      => Error::ERR_REDIS_ERROR,
                            'errCode'   => $client->errCode,
                            'errMsg'    => $client->errMsg,
                        ]);
                        return;
                    }
                    $promise->resolve([
                        'code'  => Error::SUCCESS,
                        'data'  => $result
                    ]);
                };
                call_user_func_array([$this->db, $name], $arguments);
                break;
            }
            case Constants::MODE_SYNC:
            {
                $result = call_user_func_array([$this->link, $name], $arguments);
                if( $result === false )
                {
                    $promise->resolve([
                        'code'      => Error::ERR_REDIS_ERROR
                    ]);
                    break;
                }
                $promise->resolve([
                    'code'  => Error::SUCCESS,
                    'data'  => $result
                ]);
                break;
            }
        }

        return $promise;
    }

    /**
     * 设置超时时间
     * @param $timeout     int      超时时间，单位ms
     * @return Redis                返回当前对象
     */
    public function setTimeout($timeout)
    {
        $this->timeout = $timeout;
        return $this;
    }
}

