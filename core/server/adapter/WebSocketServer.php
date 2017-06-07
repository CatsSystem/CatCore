<?php
/*******************************************************************************
 *  This file is part of CatCore.
 *
 *  CatCore is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  CatCore is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Foobar.  If not, see <http://www.gnu.org/licenses/>.
 *******************************************************************************
 * Author: Lidanyang  <simonarthur2012@gmail.com>
 ******************************************************************************/


namespace core\server\adapter;


use core\server\IServer;

abstract class WebSocketServer extends IServer
{
    /**
     * 服务器接收到TCP完整数据包后回调此函数
     * @param \swoole_server $server swoole_server对象
     * @param int $fd 连接描述符
     * @param int $from_id reactor id
     * @param string $data 接收到的数据
     */
    public function onReceive(\swoole_server $server, $fd, $from_id, $data)
    {
        // TODO: Implement onReceive() method.
    }
}