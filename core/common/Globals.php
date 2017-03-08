<?php
/**
 * Created by PhpStorm.
 * User: lidanyang
 * Date: 17/3/2
 * Time: 10:55
 */
namespace core\common;

use core\component\config\Config;

class Globals
{
    /**
     * @var \swoole_server
     */
    static public $server;

    public static $open_task = false;

    public static $open_cache = false;

    public static function isWorker()
    {
        if( empty(Globals::$server) )
        {
            return true;
        }
        return !Globals::$server->taskworker;
    }

    public static function isOpenTask()
    {
        return ( Config::getSubField('component', 'task', 'open_task', false)
        && self::$open_task );
    }

    public static function isOpenCache()
    {
        return ( Config::getSubField('component', 'cache', 'open_cache', false)
            && self::$open_cache );
    }

    public static function isOpenLog()
    {
        return Config::getSubField('component', 'log', 'open_log', false);
    }

    public static function setProcessName($name)
    {
        if(PHP_OS != 'Darwin')
        {
            swoole_set_process_name($name);
        }
    }

    public static function var_dump($string)
    {
        error_log(var_export($string, true));
    }
}