<?php
/**
 * Created by PhpStorm.
 * User: lidanyang
 * Date: 16/4/12
 * Time: 上午10:08
 */

namespace core\framework\log\adapter;


use core\framework\log\Logger;

class File extends Logger
{
    private $file_path;
    private $file = [];

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
        $log_file = $this->file_path . $path . '_' .  date("Y-m-d") . '.log';
        if( !isset($this->file[$log_file]) )
        {
            $last = $this->file_path . $path . '_' .date("Y-m-d",strtotime("-1 day")) . '.log';
            if(isset($this->file[$last]))
            {
                fclose($this->file[$last]);
                unset($this->file[$last]);
            }
            $this->file[$log_file] = fopen($log_file,'a');
        }
        if( is_string($content) )
        {
            $str = date("Y-m-d H:i:s") .": " . $content;
        } else {
            $str = date('Y-m-d H:i:s') .": " . var_export($content, true);
        }
        fwrite( $this->file[$log_file], $str . "\r\n");
    }
}