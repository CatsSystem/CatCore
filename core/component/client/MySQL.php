<?php
/**
 * Created by PhpStorm.
 * User: lidanyang
 * Date: 17/3/3
 * Time: 10:52
 */

namespace core\component\client;

use core\common\Constants;
use core\common\Error;
use core\concurrent\Promise;
use core\component\log\Log;
use core\component\pool\BasePool;

class MySQL
{
    public $id;

    /**
     * 配置选项
     * @var array
     */
    private $config;

    /**
     * @var \swoole_mysql
     */
    private $db;

    /**
     * @var BasePool
     */
    private $pool;

    /**
     * @var int
     */
    private $mode;

    /**
     * @var \mysqli
     */
    private $link;

    public function __construct($config, $mode = Constants::MODE_ASYNC)
    {
        $this->config = $config;
        $this->mode = $mode;
    }

    public function addPool($pool)
    {
        $this->pool = $pool;
    }

    public function connect($id, $timeout=3000)
    {
        $this->id = $id;
        $promise = new Promise();

        switch ($this->mode)
        {
            case Constants::MODE_ASYNC:
            {
                $this->db = new \swoole_mysql();
                $this->db->on('Close', function($db){
                    $this->close();
                });
                $timeId = swoole_timer_after($timeout, function() use ($promise){
                    $this->close();
                    $promise->reject(Error::ERR_MYSQL_TIMEOUT);
                });
                $this->db->connect($this->config, function($db, $r) use ($promise,$timeId) {
                    swoole_timer_clear($timeId);
                    if ($r === false) {
                        Log::ERROR('MySQL' , sprintf("Connect MySQL Failed [%d]: %s", $db->connect_errno, $db->connect_error));
                        $promise->reject(Error::ERR_MYSQL_CONNECT_FAILED);
                        return;
                    }
                    $promise->resolve(Error::SUCCESS);
                });
                break;
            }
            case Constants::MODE_SYNC:
            {
                $dbHost = $this->config['host'];
                $dbUser = $this->config['user'];
                $dbPwd  = $this->config['password'];
                $dbName = $this->config['database'];

                $this->link = new \mysqli($dbHost, $dbUser, $dbPwd, $dbName);

                if ($this->link->connect_error) {
                    Log::ERROR('MySQL' , sprintf("Connect MySQL Failed [%d]: %s", $this->link->connect_errno, $this->link->connect_error));
                    $promise->reject(Error::ERR_MYSQL_CONNECT_FAILED);
                }
                $promise->resolve(Error::SUCCESS);
                break;
            }
        }

        return $promise;
    }

    public function close()
    {
        switch ($this->mode)
        {
            case Constants::MODE_ASYNC:
            {
                $this->db->close();
                unset($this->db);
                $this->inPool(true);
                break;
            }
            case Constants::MODE_SYNC:
            {
                $this->link->close();
                break;
            }
        }
    }

    private function inPool($close = false)
    {
        if( !empty($this->pool) )
        {
            $this->pool->push($this, $close);
        }
    }

    public function execute($sql, $get_one, $timeout=3000)
    {
        $promise = new Promise();
        switch ($this->mode)
        {
            case Constants::MODE_ASYNC:
            {
                $timeId = swoole_timer_after($timeout, function() use ($promise, $sql){
                    $this->inPool();
                    $promise->resolve([
                        'code' => Error::ERR_MYSQL_TIMEOUT,
                    ]);
                });
                $this->db->query($sql, function($db, $result) use ($sql, $promise, $timeId, $get_one){
                    $this->inPool();
                    swoole_timer_clear($timeId);
                    if($result === false) {
                        Log::ERROR('MySQL', sprintf("%s \n [%d] %s",$sql, $db->errno, $db->error));
                        $promise->resolve([
                            'code'  => Error::ERR_MYSQL_QUERY_FAILED,
                            'errno' => $db->errno
                        ]);
                    } else if($result === true) {
                        $promise->resolve([
                            'code'          => Error::SUCCESS,
                            'affected_rows' => $db->affected_rows,
                            'insert_id'     => $db->insert_id
                        ]);
                    } else {
                        $promise->resolve([
                            'code'  => Error::SUCCESS,
                            'data'  => empty($result) ? [] : ($get_one ? $result[0] :$result)
                        ]);
                    }
                });
                break;
            }
            case Constants::MODE_SYNC:
            {
                $result = $this->link->query($sql);
                if($this->link->errno == 2006)
                {
                    $this->close();
                    $this->connect($this->id);
                    $result = $this->link->query($sql);
                }
                if($result === false) {
                    Log::ERROR('MySQL', sprintf("%s \n [%d] %s",$sql, $this->link->errno, $this->link->error));
                    $promise->resolve([
                        'code'  => Error::ERR_MYSQL_QUERY_FAILED,
                        'errno' => $this->link->errno
                    ]);
                } else if($result === true) {
                    $promise->resolve([
                        'code'          => Error::SUCCESS,
                        'affected_rows' => $this->link->affected_rows,
                        'insert_id'     => $this->link->insert_id
                    ]);
                } else {
                    $result_arr = $result->fetch_all(\MYSQLI_ASSOC);
                    $promise->resolve([
                        'code'  => Error::SUCCESS,
                        'data'  => empty($result_arr) ? [] : ($get_one ? $result_arr[0] : $result_arr)
                    ]);
                }
                break;
            }
        }

        return $promise;
    }
}