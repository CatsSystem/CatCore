<?php
/**
 * Created by PhpStorm.
 * User: lidanyang
 * Date: 16/12/2
 * Time: 下午9:53
 */
namespace core\component\cache;

use core\common\Globals;
use core\concurrent\Promise;

abstract class ILoader
{
    protected $id;

    protected $data;

    protected $tick = 1;

    protected $count;

    public function __construct()
    {
        $this->init();
    }

    public function broadcast($data)
    {
        $worker_num = Globals::$server->setting['worker_num'] - 1;
        while( $worker_num >= 0 )
        {
            Globals::$server->sendMessage(json_encode([
                'type'  => 'cache',
                'id'    => $this->id,
                'data'  => $data
            ]), $worker_num);
            $worker_num --;
        }
    }

    public function set($data)
    {
        $this->data = $data;
    }

    public function get()
    {
        return $this->data;
    }

    /**
     * 初始化加载器, 定义加载器id 和 tick 数量
     */
    abstract public function init();

    /**
     * 加载缓存内容
     * @param Promise $promise
     */
    abstract public function load(Promise $promise);

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    public function refresh()
    {
        $this->count ++;
        if( $this->count >= $this->tick ) {
            $this->count = 0;
            return true;
        }
        return false;
    }

}