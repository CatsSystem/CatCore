<?php
/**
 * Created by PhpStorm.
 * User: lidanyang
 * Date: 17/3/3
 * Time: 13:13
 */

namespace core\framework\pool\adapter;

use core\common\Constants;
use core\common\Globals;
use core\concurrent\Promise;
use core\framework\pool\BasePool;
use core\framework\client\Redis as Driver;

class Redis extends BasePool
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
        $this->config['size'] = 1;
        parent::__construct($config['name'], $this->config['size']);
    }

    public function init()
    {
        if(Globals::isWorker())
        {
            for($i = 0; $i < $this->size; $i ++)
            {
                $this->new_item($i + 1);
            }
        }
        $this->sync = new Driver($this->config['args'], Constants::MODE_SYNC);
        $this->sync->connect(0);
    }

    /**
     * 弹出一个空闲item
     * @return mixed
     */
    public function pop()
    {
        if(Globals::isWorker())
        {
            if( $this->idle_queue->isEmpty() )
            {
                $promise = new Promise();
                $this->waiting_tasks->enqueue($promise);
                return $promise;
            }
            $driver = $this->idle_queue->dequeue();
            $this->idle_queue->enqueue($driver);
            return $driver;
        }
        else
        {
            return $this->sync;
        }
    }

    /**
     * @param $item
     * @param bool $close
     * @return void
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

}