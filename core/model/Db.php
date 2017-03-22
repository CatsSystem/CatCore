<?php
/**
 * Created by PhpStorm.
 * User: lidanyang
 * Date: 17/3/22
 * Time: 16:11
 */
namespace core\model;
use Aura\SqlQuery\QueryFactory;
use core\component\client\MySQL;
use core\concurrent\Promise;


/**
 * Db类,封装了Aura SqlQuery相关操作
 * Class Db
 * @package core\model
 */
class Db
{
    /**
     * @var QueryFactory
     */
    private static $factory = null;

    protected static function init()
    {
        if( empty(self::$factory) )
        {
            self::$factory = new QueryFactory('mysql');
        }
    }

    public static function select()
    {
        return self::$factory->newSelect();
    }

    public static function update()
    {
        return self::$factory->newUpdate();
    }

    public static function insert()
    {
        return self::$factory->newInsert();
    }

    public static function delete()
    {
        return self::$factory->newDelete();
    }

    /**
     * 执行SQL语句
     * @param $driver MySQL | Promise
     * @return mixed
     */
    public function query($driver)
    {
        if( $driver instanceof MySQL ) {
            $result = $driver->execute($this->sql(), false);
            return $result;
        } else {
            return $driver->then(function(MySQL $driver) {
                $result = $driver->execute($this->sql(), false);
                return $result;
            });
        }
    }

    /**
     * 执行SQL语句
     * @param $driver MySQL | Promise
     * @return mixed
     */
    public function getOne($driver)
    {
        if( $driver instanceof MySQL ) {
            $result = $driver->execute($this->sql(), true);
            return $result;
        } else {
            return $driver->then(function(MySQL $driver) {
                $result = $driver->execute($this->sql(), true);
                return $result;
            });
        }
    }
}