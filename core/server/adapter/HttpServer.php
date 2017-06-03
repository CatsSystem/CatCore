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


abstract class HttpServer extends IServer
{
    /**
     * 服务器接收到TCP完整数据包后回调此函数
     * @param \swoole_server $server swoole_server对象
     * @param int $fd 连接描述符
     * @param int $from_id reactor id
     * @param string $data 接收到的数据
     */
    public function onReceive($server, $fd, $from_id, $data)
    {
        // TODO: Implement onReceive() method.
    }

    /**
     * 服务器接收到WebSocket完整数据包后回调此函数
     * @param \swoole_websocket_server $server swoole_websocket_server对象
     * @param \swoole_websocket_frame $frame swoole封装的ws请求帧
     */
    public function onMessage(\swoole_websocket_server $server, \swoole_websocket_frame $frame)
    {
        // TODO: Implement onMessage() method.
    }

    /**
     * 服务器接收到WebSocket连接时回调此函数
     * @param \swoole_websocket_server $server swoole_websocket_server对象
     * @param \swoole_http_request $request swoole封装的http请求对象
     */
    public function onOpen(\swoole_websocket_server $server, \swoole_http_request $request)
    {
        // TODO: Implement onOpen() method.
    }

    /**
     * 服务器进行WebSocket握手操作时回调此函数
     * @param \swoole_http_request $request swoole封装的http请求对象
     * @param \swoole_http_response $response swoole封装的http应答对象
     */
    public function onHandShake(\swoole_http_request $request, \swoole_http_response $response)
    {
        // TODO: Implement onHandShake() method.
    }
}