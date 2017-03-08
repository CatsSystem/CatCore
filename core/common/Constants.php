<?php
/**
 * Created by PhpStorm.
 * User: lidanyang
 * Date: 16/7/31
 * Time: 上午1:34
 */

namespace core\common;

class Constants
{
    const ONE_TICK                          = 10000;         // 内存缓存更新的TICK间隔,默认10秒
    
    const ONE_HOUR                          = 3600;
    const ONE_DAY                           = 86400;
    const ONE_MONTH                         = 2592000;

    const MODE_ASYNC                        = 1;
    const MODE_SYNC                         = 2;

}
