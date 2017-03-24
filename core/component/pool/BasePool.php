<?php
/**
 * Created by PhpStorm.
 * User: lidanyang
 * Date: 17/3/3
 * Time: 12:57
 */

namespace core\component\pool;

abstract class BasePool
{
    /**
     * @var \SplQueue 空闲队列
     */
    protected $idle_queue;

    /**
     * @var \SplQueue 等待队列
     */
    protected $waiting_tasks;

    /**
     * @var int 池大小
     */
    protected $size;

    /**
     * @var string 名称
     */
    protected $name;

    public function __construct($name, $size)
    {
        $this->name         = $name;
        $this->size         = $size;
        $this->idle_queue   = new \SplQueue();
        $this->waiting_tasks = new \SplQueue();
    }

    /**
     * 弹出一个空闲item
     * @param bool $force_sync  强制使用同步模式
     * @return mixed
     */
    abstract public function pop($force_sync = false);

    /**
     * 归还一个item
     * @param $item
     * @param bool $close   是否关闭
     */
    abstract public function push($item, $close = false);

    /**
     * 初始化连接池
     */
    abstract public function init();

    /**
     * 创建一个新的连接
     * @param $id
     * @return mixed
     */
    abstract protected function new_item($id);


    /**
     * 获取连接池名称
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
}