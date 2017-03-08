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

    private $config;

    /**
     * @var array[BasePool]
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

    public function get($name)
    {
        return $this->pools[$name] ?? null;
    }


}