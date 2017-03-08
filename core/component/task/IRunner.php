<?php
/**
 * Created by PhpStorm.
 * User: lidanyang
 * Date: 16/12/6
 * Time: 下午11:15
 */

namespace core\framework\task;

use core\common\Error;

abstract class IRunner
{
    /**
     * Call a undefined task
     * @param $name
     * @param $arguments
     * @return int
     */
    public function __call($name, $arguments)
    {
        return Error::ERR_TASK_NOT_FOUND;
    }
}