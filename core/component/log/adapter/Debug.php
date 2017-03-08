<?php
/**
 * Created by PhpStorm.
 * User: lidanyang
 * Date: 17/3/3
 * Time: 10:21
 */

namespace core\component\log\adapter;


use core\component\log\Logger;

class Debug extends Logger
{
    public function __construct($config)
    {
        parent::__construct($config);
    }

    protected function save($path, $content)
    {
        if( !$this->config['open_log'] )
        {
            return;
        }
        if( is_string($content) )
        {
            $str = date("Y-m-d H:i:s") .": " . $content;
        } else {
            $str = date('Y-m-d H:i:s') .": " . var_export($content, true);
        }
        error_log($str);
    }
}