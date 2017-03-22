<?php
/**
 * Created by PhpStorm.
 * User: lidanyang
 * Date: 17/3/8
 * Time: 12:57
 */

namespace core\component\task;

use core\common\Globals;
use core\concurrent\Promise;

class AsyncTask
{
    private $name;
    public function __construct($name)
    {
        $this->name = $name;
    }
    public function __call($name, $arguments)
    {
        if(!Globals::isWorker())
        {
            throw new \Exception("Can not use task in Task Worker");
        }
        $promise = new Promise();
        if( !Globals::isOpenTask() )
        {
            $promise->resolve(false);
            return $promise;
        }
        $data = \swoole_serialize::pack([
            'task'    => $this->name,
            'method'  => $name,
            'params'  => $arguments
        ]);
        Globals::$server->task($data, -1, function(\swoole_server $serv, $task_id, $data) use ($promise) {
            $promise->resolve(\swoole_serialize::unpack($data));
        });
        return $promise;
    }
}