<?php
/**
 * Created by PhpStorm.
 * User: lidanyang
 * Date: 17/3/3
 * Time: 12:57
 */

namespace core\component\pool;

use core\component\config\Config;

class PoolManager
{
    private static $instance = null;

    /**
     * @return PoolManager
     */
    public static function getInstance()
    {
        if(PoolManager::$instance == null)
        {
            PoolManager::$instance = new PoolManager();
        }
        return PoolManager::$instance;
    }

    /**
     * @var array 配置数据
     */
    private $config;

    /**
     * @var array[BasePool] 连接池的实例数组
     */
    private $pools = [];
    
    protected function __construct()
    {
        $config = Config::get('pool');
        foreach ($config as $name => $pool)
        {
            $pool['name'] = $name;
            $this->config[$name] = $pool;
        }
    }

    /**
     * 初始化一个连接池
     * @param $name     string      连接池的配置名称
     * @return bool                 创建成功返回true
     */
    public function init($name)
    {
        if( !isset($this->config[$name] ))
        {
            return false;
        }
        if( !isset($this->pools[$name]) )
        {
            $this->pools[$name] = PoolFactory::getInstance($this->config[$name]);
            if( empty($this->pools[$name]) )
            {
                return false;
            }
            $this->pools[$name]->init();
        }
        return true;
    }

    /**
     * 根据名称获取一个指定的连接池实例
     * @param $name             string      连接池的配置名称
     * @return BasePool|null                   连接池存在则返回,不存在返回null
     */
    public function get($name)
    {
        return $this->pools[$name] ?? null;
    }


}