<?php
/**
 * Created by PhpStorm.
 * User: lidanyang
 * Date: 16/7/31
 * Time: 上午1:34
 */

namespace core\common;

/**
 * 常量
 * Class Constants
 * @package core\common
 */
class Constants
{
    /**
     * 内存缓存更新的TICK间隔,默认10秒
     */
    const ONE_TICK                          = 10000;
    
    const ONE_HOUR                          = 3600;             // 1小时的秒数
    const ONE_DAY                           = 86400;            // 1天的秒数
    const ONE_MONTH                         = 2592000;          // 1个月的秒数

    const MODE_ASYNC                        = 1;                // 异步模式
    const MODE_SYNC                         = 2;                // 同步模式

}
