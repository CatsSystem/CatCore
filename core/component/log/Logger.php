<?php
/**
 * Created by PhpStorm.
 * User: lidanyang
 * Date: 16/4/12
 * Time: 上午10:06
 */

namespace core\framework\log;

abstract class Logger
{
    protected $config;

    private $level = 1;

    public function __construct($config)
    {
        $this->level = $config['log_level'];
        $this->config = $config;
    }

    public function debug($tag, $content)
    {
        if( $this->level >= 2 )
        {
            return;
        }
        $path = $tag . "_DEBUG";
        $this->save($path, $content);
    }
    public function error($tag, $content)
    {
        if( $this->level >= 5)
        {
            return;
        }
        $path = $tag . "_ERROR";
        $this->save($path, $content);
    }
    public function info($tag, $content)
    {
        if( $this->level >= 4)
        {
            return;
        }
        $path = $tag . "_INFO";
        $this->save($path, $content);
    }
    public function warning($tag, $content)
    {
        if( $this->level >= 3)
        {
            return;
        }
        $path = $tag . "_WARNING";
        $this->save($path, $content);
    }

    abstract protected function save($path, $content);
}