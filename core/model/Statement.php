<?php
/**
 * Created by PhpStorm.
 * User: lidanyang
 * Date: 17/3/24
 * Time: 13:38
 */

namespace core\model;

use core\component\client\MySQL;

class Statement
{

    private static $instances = [];

    /**
     * @var Mysql
     */
    private static $client;

    /**
     * @param $sql
     * @return Statement
     */
    public static function prepare($sql) {
        if( isset(self::$instances[$sql]) ) {
            return self::$instances[$sql];
        }
        self::$instances[$sql] = new Statement($sql);
        return self::$instances[$sql];
    }

    /**
     * @param $client Mysql
     */
    public static function init($client)
    {
        self::$client = $client;
    }

    /**
     * @var string 待预处理的SQL语句
     */
    private $sql;

    protected function __construct($sql)
    {
        $this->sql = $sql;
    }

    public function bindValues(array $values)
    {
        $patterns = [];
        $replacements = [];
        foreach ($values as $key => $value)
        {
            $patterns[]     = "/:$key/";

            if( is_numeric($value) ) {
                $replacements[] = $value;
            } else if(is_array($value)) {
                $str = "";
                foreach ($value as $v)
                {
                    if ($str) $str.=",";
                    $str.="'".addslashes($v)."'";
                }
                $replacements[] = $str;
            } else {
                $replacements[] = "'" . self::$client->escape($value) . "'";
            }

        }
        ksort($patterns);
        ksort($replacements);
        return preg_replace($patterns, $replacements, $this->sql);
    }

}