<?php
/**
 * Created by PhpStorm.
 * User: lidanyang
 * Date: 17/3/3
 * Time: 17:31
 */
namespace core\framework\pool;

class PoolFactory
{
    /**
     * @param $config
     * @return BasePool
     * @throws \Exception
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
            throw new \Exception("no class {$class_name}");
        }
        return new $class_name($config);
    }
}