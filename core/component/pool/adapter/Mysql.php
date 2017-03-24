<?php
/**
 * Created by PhpStorm.
 * User: lidanyang
 * Date: 17/3/3
 * Time: 13:13
 */

namespace core\component\pool\adapter;

use core\common\Constants;
use core\common\Globals;
use core\concurrent\Promise;
use core\component\pool\BasePool;
use core\component\client\MySQL as Driver;

class Mysql extends BasePool
{
    private $config;

    /**
     * @var Driver
     */
    private $sync;

    public function __construct($config)
    {
        $this->config = $config;
        $this->config['name'] = $this->config['name'] ?? __FILE__;
        $this->config['size'] = $this->config['size'] ?? 5;
        parent::__construct($config['name'], $this->config['size']);
    }

    public function init()
    {
        if(Globals::isWorker()) {
            for($i = 0; $i < $this->size; $i ++)
            {
                $this->new_item($i + 1);
            }
        } else {
            $this->sync = new Driver($this->config['args'], Constants::MODE_SYNC);
            $this->sync->connect(0);
        }
    }

    /**
     * 弹出一个空闲item
     * @param bool $force_sync      强制使用同步模式
     * @return mixed
     */
    public function pop($force_sync = false)
    {
        if(Globals::isWorker() && !$force_sync)
        {
            while( !$this->idle_queue->isEmpty() )
            {
                $driver = $this->idle_queue->dequeue();
                if( $driver->isClose() )
                {
                    continue;
                }
                return $driver;
            }

            $promise = new Promise();
            $this->waiting_tasks->enqueue($promise);
            return $promise;
        }
        else
        {
            return $this->sync;
        }
    }

    protected function new_item($id)
    {
        $driver = new Driver($this->config['args']);
        $driver->addPool($this);
        $driver->connect($id)->then(function() use ($driver){
            $this->idle_queue->enqueue($driver);
            if( $this->waiting_tasks->count() > 0 )
            {
                $this->doTask();
            }
        }, function() use ($id){
            $this->new_item($id);
        });
    }

    protected function doTask()
    {
        $promise = $this->waiting_tasks->dequeue();
        $driver = $this->idle_queue->dequeue();
        $promise->resolve($driver);
    }

    /**
     * 归还一个item
     * @param $item
     * @param bool $close 是否关闭
     */
    public function push($item, $close = false)
    {
        if($close)
        {
            $this->new_item($item->id);
            unset($item);
            return;
        }
        $this->idle_queue->enqueue($item);
        return;
    }

    public function escape($value)
    {
        return $this->sync->escape($value);
    }
}