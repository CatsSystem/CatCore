<?php
/**
 * Created by PhpStorm.
 * User: lidanyang
 * Date: 16/4/12
 * Time: 上午10:08
 */

namespace core\framework\log\adapter;


use core\framework\log\Logger;

class Swoole extends Logger
{
    private $file_path;

    public function __construct($config)
    {
        parent::__construct($config);
        $this->file_path = isset($config['path']) ? $config['path'] : '/var/log/swoole/';

        if( !file_exists($this->file_path) )
        {
            @mkdir($this->file_path, 0755, true);
        }
    }

    protected function save($path, $content)
    {
        if( !$this->config['open_log'] )
        {
            return;
        }
        $log_file = $this->file_path . $path . '_' .  date("Y-m-d");
        if( is_string($content) )
        {
            $str = date("Y-m-d H:i:s") .": " . $content;
        } else {
            $str = date('Y-m-d H:i:s') .": " . var_export($content, true);
        }
        swoole_async_writefile($log_file,$str, function(){}, FILE_APPEND);
    }
}