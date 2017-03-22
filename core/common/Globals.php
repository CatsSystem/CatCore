<?php
/**
 * Created by PhpStorm.
 * User: lidanyang
 * Date: 17/3/2
 * Time: 10:55
 */
namespace core\common;

use core\component\config\Config;

/**
 * 全局静态变量集合
 * Class Globals
 * @package core\common
 */
class Globals
{
    /**
     * @var \swoole_server    全局server对象
     */
    static public $server;

    /**
     * @var bool        是否开启缓存组件功能
     */
    public static $open_cache = false;

    /**
     * 判断当前进程是否为Worker进程
     * @return bool
     */
    public static function isWorker()
    {
        if( empty(Globals::$server) )
        {
            return true;
        }
        return !Globals::$server->taskworker;
    }

    /**
     * 判断是否开启了异步Task功能
     * @return bool
     */
    public static function isOpenTask()
    {
        return Config::getSubField('component', 'task', 'open_task', false);
    }

    /**
     * 判断是否开启了内存Cache功能
     * @return bool
     */
    public static function isOpenCache()
    {
        return ( Config::getSubField('component', 'cache', 'open_cache', false)
            && self::$open_cache );
    }

    /**
     * 判断是否开启了日志功能
     * @return bool
     */
    public static function isOpenLog()
    {
        return Config::getSubField('component', 'log', 'open_log', false);
    }

    /**
     * 设置进程名
     * @param $name     string      进程名称
     */
    public static function setProcessName($name)
    {
        if(PHP_OS != 'Darwin')
        {
            swoole_set_process_name($name);
        }
    }

    /**
     * 打印信息到终端(打印的信息不会被ob系列函数捕获)
     * @param $string     string  待打印的数据
     */
    public static function var_dump($string)
    {
        error_log(var_export($string, true));
    }
}