<?php
/**
 * Created by PhpStorm.
 * User: lidanyang
 * Date: 17/3/3
 * Time: 17:31
 */
namespace core\component\pool;

class PoolFactory
{
    /**
     * 根据指定的配置数据创建一个连接池实例
     * @param $config   array   配置数据, 键值对存储
     * @return BasePool | null  创建好的连接池, 连接池类型不存在返回null
     */
    public static function getInstance($config)
    {
        if( empty($config) || !isset($config['type']))
        {
            return null;
        }
        $type = ucfirst(strtolower($config['type']));

        $class_name = __NAMESPACE__ . '\\adapter\\' . $type;

        if( !class_exists($class_name) )
        {
            return null;
        }
        return new $class_name($config);
    }
}