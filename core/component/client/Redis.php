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

class Redis
{

    public $id;

    /**
     * @var \swoole_redis
     */
    private $db;

    /**
     * @var int
     */
    private $timeout = 3000;

    /**
     * @var BasePool
     */
    private $pool;

    /**
     * @var int
     */
    private $mode;

    /**
     * @var \Redis
     */
    private $link;

    public function __construct($config,  $mode = Constants::MODE_ASYNC)
    {
        $this->config = $config;
        $this->mode = $mode;
    }

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

                }

                break;
            }
        }

        return $promise;
    }

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
                call_user_func_array([$this->link, $name], $arguments);
                break;
            }
        }

        return $promise;
    }

    /**
     * @param mixed $timeout
     * @return Redis
     */
    public function setTimeout($timeout)
    {
        $this->timeout = $timeout;
        return $this;
    }
}

