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


use core\common\Globals;

abstract class IServer
{
    /**
     * @var string 项目名称
     */
    private $project_name;

    /**
     * @var string PID文件存放路径
     */
    private $pid_path;

    public function __construct($project_name, $pid_path = '/var/run')
    {
        $this->project_name = $project_name;
        $this->pid_path     = $pid_path;
    }

    public function onStart(\swoole_server $server)
    {
        Globals::setProcessName($this->project_name . " server running master:" . $server->master_pid);
        if (!empty($this->pid_path)) {
            file_put_contents($this->pid_path . DIRECTORY_SEPARATOR . $this->project_name . '_master.pid', $server->master_pid);
        }
    }

    /**
     * @throws \Exception
     */
    public function onShutDown()
    {
        if (!empty($this->pid_path)) {
            $filename = $this->pid_path . DIRECTORY_SEPARATOR . $this->project_name . '_master.pid';
            if (is_file($filename)) {
                unlink($filename);
            }
            $filename = $this->pid_path . DIRECTORY_SEPARATOR . $this->project_name . '_manager.pid';
            if (is_file($filename)) {
                unlink($filename);
            }
        }
    }

    /**
     * @param $server
     * @throws \Exception
     * @desc 服务启动，设置进程名
     */
    public function onManagerStart(\swoole_server $server)
    {
        Globals::setProcessName($this->project_name .' server manager:' . $server->manager_pid);
        if (!empty($this->pid_path)) {
            file_put_contents($this->pid_path . DIRECTORY_SEPARATOR . $this->project_name . '_manager.pid', $server->manager_pid);
        }
    }

    public function onManagerStop()
    {
        if (!empty($this->pid_path)) {
            $filename = $this->pid_path . DIRECTORY_SEPARATOR . $this->project_name . '_manager.pid';
            if (is_file($filename)) {
                unlink($filename);
            }
        }
    }

    /**
     * @param \swoole_server    $server         swoole_server对象
     * @param int               $workerId       Worker进程ID
     */
    public function doWorkerStart(\swoole_server $server, $workerId)
    {
        $workNum = $server->setting['worker_num'];
        if ($workerId >= $workNum) {
            swoole_set_process_name("Push Server tasker num: ".($server->worker_id - $workNum)." pid " . $server->worker_pid);
        } else {
            swoole_set_process_name("Push Server worker  num: {$server->worker_id} pid " . $server->worker_pid);
        }
        $this->onWorkerStart($server, $workerId);
    }

    /**
     * Worker进程退出时调用此函数
     * @param \swoole_server    $server         swoole_server对象
     * @param int               $workerId       Worker进程ID
     **/
    public function onWorkerStop(\swoole_server $server, $workerId)
    {

    }

    /**
     * @param \swoole_server    $server         swoole_server对象
     * @param int               $workerId       Worker进程ID
     * @param int               $worker_pid     Worker进程PID
     * @param int               $exit_code      退出的错误码
     * @param int               $signal         进程退出的信号
     */
    public function onWorkerError(\swoole_server $server, $workerId, $worker_pid, $exit_code, $signal)
    {

    }


    /**
     * 初始化函数，在swoole_server启动前执行
     * @param \swoole_server $server
     */
    abstract public function init(\swoole_server $server);

    /**
     * Worker进程启动前回调此函数
     * @param \swoole_server    $server         swoole_server对象
     * @param int               $workerId       Worker进程ID
     */
    abstract public function onWorkerStart(\swoole_server $server, $workerId);

    /**
     * 服务器接收新连接时调用此函数
     * @param \swoole_server    $server         swoole_server对象
     * @param int               $fd             连接描述符
     */
    abstract public function onConnect(\swoole_server $server, $fd);

    /**
     * 连接断开时调用此函数
     * @param \swoole_server    $server         swoole_server对象
     * @param int               $fd             连接描述符
     */
    abstract public function onClose(\swoole_server $server, $fd);

    /**
     * 当Worker进程投递任务到Task Worker进程时调用此函数
     * @param \swoole_server    $server         swoole_server对象
     * @param int               $task_id        任务ID
     * @param int               $src_worker_id  发起任务的Worker进程ID
     * @param mixed             $data           任务数据
     */
    abstract public function onTask(\swoole_server $server, $task_id, $src_worker_id, $data);


    /**
     * 服务器接收到TCP完整数据包后回调此函数
     * @param \swoole_server    $server         swoole_server对象
     * @param int               $fd             连接描述符
     * @param int               $from_id        reactor id
     * @param string            $data           接收到的数据
     */
    abstract public function onReceive(\swoole_server $server, $fd, $from_id, $data);

    /**
     * 服务器接收到Http完整数据包后回调此函数
     * @param \swoole_http_request      $request        swoole封装的http请求对象
     * @param \swoole_http_response     $response       swoole封装的http应答对象
     */
    abstract public function onRequest(\swoole_http_request $request, \swoole_http_response $response);

    /**
     * 服务器接收到WebSocket完整数据包后回调此函数
     * @param \swoole_websocket_server  $server         swoole_websocket_server对象
     * @param \swoole_websocket_frame   $frame          swoole封装的ws请求帧
     */
    abstract public function onMessage(\swoole_websocket_server $server, \swoole_websocket_frame $frame);

    /**
     * 服务器接收到WebSocket连接时回调此函数
     * @param \swoole_websocket_server  $server         swoole_websocket_server对象
     * @param \swoole_http_request      $request        swoole封装的http请求对象
     */
    abstract public function onOpen(\swoole_websocket_server $server, \swoole_http_request $request);

    /**
     * 服务器进行WebSocket握手操作时回调此函数
     * @param \swoole_http_request      $request        swoole封装的http请求对象
     * @param \swoole_http_response     $response       swoole封装的http应答对象
     */
    abstract public function onHandShake(\swoole_http_request $request, \swoole_http_response $response);

    /**
     * Swoole进程间通信的回调函数
     * @param \swoole_server    $server         swoole_server对象
     * @param int               $from_worker_id 来源Worker进程ID
     * @param mixed             $message        消息内容
     */
    abstract public function onPipeMessage(\swoole_server $server, $from_worker_id, $message);
}