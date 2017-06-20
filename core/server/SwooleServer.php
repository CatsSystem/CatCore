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


namespace core\server;


class SwooleServer
{
    /**
     * @var \swoole_server | \swoole_http_server | \swoole_websocket_server
     */
    protected $server;

    /**
     * @var IServer
     */
    protected $main_server;

    /**
     * @var array
     */
    protected $config;

    public function __construct($config)
    {
        $this->config = $config;

        switch (strtolower($this->config['socket_type']))
        {
            case 'tcp':
            {
                $this->server = new \swoole_server($config['host'], $config['port'], SWOOLE_PROCESS, $this->getType());
                break;
            }
            case 'http2':
            {
                $this->config['setting']['open_http2_protocol'] = true;
                $this->server = new \swoole_http_server($config['host'], $config['port'], SWOOLE_PROCESS, $this->getType());
                break;
            }
            case 'http':
            {
                $this->server = new \swoole_http_server($config['host'], $config['port'], SWOOLE_PROCESS, $this->getType());
                break;
            }
            case 'ws':
            {
                $this->server = new \swoole_websocket_server($config['host'], $config['port'], SWOOLE_PROCESS, $this->getType());
                break;
            }
        }
        $this->server->set($this->config['setting']);
    }

    public function run($main_server)
    {
        if(!$main_server instanceof IServer)
        {
            throw new \Exception("Invalid IServer Instance");
        }
        $this->main_server = $main_server;

        $this->server->on("Start", [$this->main_server, "onStart"]);
        $this->server->on("Shutdown", [$this->main_server, "onShutDown"]);
        $this->server->on("ManagerStart", [$this->main_server, "onManagerStart"]);
        $this->server->on("ManagerStop", [$this->main_server, "onManagerStop"]);
        $this->server->on("WorkerStart", [$this->main_server, "doWorkerStart"]);
        $this->server->on("PipeMessage", [$this->main_server, "onPipeMessage"]);

        switch (strtolower($this->config['socket_type']))
        {
            case 'tcp':
            {
                $this->server->on("Receive", [$this->main_server, "onReceive"]);
                $this->server->on("Connect", [$this->main_server, "onConnect"]);
                $this->server->on("Close",   [$this->main_server, "onClose"]);
                break;
            }
            case 'http2':
            case 'http':
            {
                $this->server->on("Request", [$this->main_server, "onRequest"]);
                break;
            }
            case 'ws':
            {
                $this->server->on("Request", [$this->main_server, "onRequest"]);
                $this->server->on("Message", [$this->main_server, "onMessage"]);
                $this->server->on("Open",    [$this->main_server, "onOpen"]);
                break;
            }
        }

        if(isset($this->config['setting']['task_worker_num'])
            && $this->config['setting']['task_worker_num'] > 0)
        {
            $this->server->on("Task", [$this->main_server, "onTask"]);
            $this->server->on("Finish", function(){});
        }

        $this->main_server->init($this->server);

        $this->server->start();
    }

    private function getType()
    {
        if( !isset($this->config['enable_ssl']))
        {
            return SWOOLE_TCP;
        }

        if( $this->config['enable_ssl'] )
        {
            if( !isset($this->config['setting']['ssl_cert_file'])
                || !isset($this->config['setting']['ssl_key_file']) )
            {
                return SWOOLE_TCP;
            }
            else
            {
                return SWOOLE_TCP | SWOOLE_SSL;
            }
        }
        return SWOOLE_TCP;
    }

}