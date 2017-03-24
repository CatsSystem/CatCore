<?php
/**
 * Created by PhpStorm.
 * User: lidanyang
 * Date: 16/4/15
 * Time: 下午3:54
 */
namespace core\common;

/**
 * 组件默认错误码
 * Class Error
 * @package core\common
 */
class Error
{
    const SUCCESS = 0;                                                  // 成功

    const ERR_INVALID_DATA                           = -1;              // 非法数据
    const ERR_EXCEPTION                              = -2;              // 异常
    const ERR_NO_DATA                                = -4;              // 数据不存在

    const ERR_MYSQL_TIMEOUT                          = -10;             // 数据库超时
    const ERR_MYSQL_QUERY_FAILED                     = -11;             // 查询失败
    const ERR_MYSQL_CONNECT_FAILED                   = -12;             // 连接失败

    const ERR_REDIS_CONNECT_FAILED                   = -20;             // Redis连接失败
    const ERR_REDIS_ERROR                            = -21;             // Redis请求失败
    const ERR_REDIS_TIMEOUT                          = -22;             // Redis超时

    const ERR_HTTP_TIMEOUT                           = -25;             // Http请求超时

    const ERR_TASK_NOT_FOUND                         = -30;             // 异步Task不存在

    const ERR_CACHE_LOAD_FAILED                      = -35;             // 加载内存缓存失败

    const ERR_END                                    = -99;             // 末尾错误码, 无意义
}
