<?php
/**
 * Created by PhpStorm.
 * User: lidanyang
 * Date: 16/4/15
 * Time: 下午3:54
 */
namespace core\common;

class Error
{
    const SUCCESS = 0;

    const ERR_INVALID_DATA                           = -1;
    const ERR_EXCEPTION                              = -2;
    const ERR_NO_DATA                                = -4;

    const ERR_MYSQL_TIMEOUT                          = -10;
    const ERR_MYSQL_QUERY_FAILED                     = -11;
    const ERR_MYSQL_CONNECT_FAILED                   = -12;

    const ERR_REDIS_CONNECT_FAILED                   = -20;
    const ERR_REDIS_ERROR                            = -21;
    const ERR_REDIS_TIMEOUT                          = -22;

    const ERR_HTTP_TIMEOUT                           = -25;

    const ERR_TASK_NOT_FOUND                         = -30;


    const ERR_END                                    = -99;
}
