<?php
/**
 * Created by PhpStorm.
 * User: lidanyang
 * Date: 16/12/2
 * Time: 下午9:53
 */
namespace core\framework\cache;

use core\common\Error;
use core\common\Globals;
use core\concurrent\Promise;

class CacheLoader
{
    private static $instance = null;

    /**
     * @return CacheLoader
     */
    public static function getInstance()
    {
        if(CacheLoader::$instance == null)
        {
            CacheLoader::$instance = new CacheLoader();
        }
        return CacheLoader::$instance;
    }
    
    public function __construct()
    {
    
    }

    /**
     * @param $init_callback  callable   
     * @return bool|\swoole_process
     */
    public static function open_cache_process($init_callback)
    {
        if( !is_callable($init_callback) )
        {
            return false;
        }
        Globals::$open_cache = true;
        if( Globals::isOpenCache() )
        {
            $process = new \swoole_process(function(\swoole_process $worker) use ($init_callback) {
                $result = call_user_func($init_callback);
                Globals::setProcessName($result['name']);
                CacheLoader::getInstance()->init($result['path']);
                CacheLoader::getInstance()->load(true);
                swoole_timer_tick($result['tick'], function(){
                    CacheLoader::getInstance()->load();
                });
            }, false, false);
            return $process;
        }
        return false;
    }

    /**
     * @var array(ILoader)
     */
    private $loaders = [];

    public function init($cache_file_path)
    {
        if( !file_exists($cache_file_path) )
        {
            return;
        }
        $files = new \DirectoryIterator($cache_file_path);
        foreach ($files as $file) {
            $filename = $file->getFilename();
            if ($filename[0] === '.') {
                continue;
            }
            if (!$file->isDir()) {
                $loader = substr($filename, 0, strpos($filename, '.'));
                $class_name = str_replace('/','\\',$cache_file_path) . $loader;
                $ob = new $class_name();
                if( ! $ob instanceof ILoader ) {
                    continue;
                }
                $this->loaders[$ob->getId()] = $ob;
            }
        }
    }

    public function load($force=false)
    {
        foreach ($this->loaders as $loader)
        {
            if( $force || $loader->refresh() ) {
                $promise = new Promise();
                $promise->then(function($value) use ($loader){
                    if( $value['code'] == Error::SUCCESS )
                    {
                        $loader->broadcast($value['data']);
                    }
                });
                Promise::co(function() use ($loader, &$promise){
                    $loader->load($promise);
                });

            }
        }
    }

    public function set($id, $data)
    {
        $this->loaders[$id]->set($data);
    }

    public function get($id)
    {
        return $this->loaders[$id]->get();
    }
}