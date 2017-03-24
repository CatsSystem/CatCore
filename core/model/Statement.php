<?php
/**
 * Created by PhpStorm.
 * User: lidanyang
 * Date: 17/3/24
 * Time: 13:38
 */

namespace core\model;

class Statement
{

    private static $instances = [];

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
            $patterns[]     = "/$key/";
            $replacements[] = $value;
        }
        ksort($patterns);
        ksort($replacements);
        return preg_replace($patterns, $replacements, $this->sql);
    }

}