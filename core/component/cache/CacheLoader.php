<?php
/**
 * Created by PhpStorm.
 * User: lidanyang
 * Date: 16/12/2
 * Time: 下午9:53
 */
namespace core\component\cache;

use core\common\Error;
use core\common\Globals;
use core\concurrent\Promise;

/**
 * 内存缓存管理类
 * Class CacheLoader
 * @package core\component\cache
 */
class CacheLoader
{
    private static $instance = null;

    /**
     * 单例入口
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
     * 开启内存缓存进程
     * @param $init_callback  callable   回调函数,用于初始化内存缓存进程
     * @return bool|\swoole_process         开启成功返回进程对象, 否则返回false
     */
    public static function openCacheProcess($init_callback)
    {
        if( !is_callable($init_callback) )
        {
            return false;
        }
        Globals::$open_cache = true;
        if( Globals::isOpenCache() )
        {
            $process = new \swoole_process(function(\swoole_process $worker) use ($init_callback) {
                $tick = call_user_func($init_callback);
                CacheLoader::getInstance()->load(true);
                swoole_timer_tick($tick, function(){
                    CacheLoader::getInstance()->load();
                });
            }, false, false);
            return $process;
        }
        return false;
    }

    /**
     * 回调函数,用于接收进程间通信结果, 用于绑定到Swoole扩展的同名回调
     * @param $data     string      通信数据
     */
    public static function onPipeMessage($data)
    {
        $data = json_decode($data, true);
        if( is_array($data) && $data['type'] == 'cache' )
        {
            CacheLoader::getInstance()->set($data['id'], $data['data']);
        }
    }

    /**
     * @var array(ILoader)
     */
    private $loaders = [];

    /**
     * 初始化内存管理
     * @param $cache_file_path      string      加载器的文件目录
     * @param $namespace            string      加载器所属的命名空间
     */
    public function init($cache_file_path, $namespace)
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
                $class_name = str_replace('/','\\',$namespace) . $loader;
                $ob = new $class_name();
                if( ! $ob instanceof ILoader ) {
                    continue;
                }
                $this->loaders[$ob->getId()] = $ob;
            }
        }
    }

    /**
     * 加载内存缓存
     * @param $force       bool     强制刷新缓存
     */
    public function load($force=false)
    {
        Promise::co(function() use ($force){
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
                    yield $loader->load($promise);
                }
            }
        });
    }

    /**
     * 设置指定ID对应的缓存数据
     * @param $id       int         缓存ID
     * @param $data     string      缓存内容
     */
    public function set($id, $data)
    {
        $this->loaders[$id]->set($data);
    }

    /**
     * 获取指定ID对应的缓存数据
     * @param $id       int         缓存ID
     * @return string               缓存内容
     */
    public function get($id)
    {
        return $this->loaders[$id]->get();
    }
}