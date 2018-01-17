<?php
namespace Controller;

use Controller\Task\Launch;

class Server extends Controller
{
    private $ip = '192.168.100.199';

    private $port = '9501';

    public function Run()
    {
        $serv = new \swoole_server($this->ip, $this->port, SWOOLE_PROCESS, SWOOLE_SOCK_TCP);
        $serv->set(array(
            'worker_num'     => 4,
            'daemonize'      => false,
            'max_request'    => 50,//处理完50个进程自动销毁重建
            'dispatch_mode'  => 3,//抢占模式底 层会屏蔽onConnect/onClose事件，原因是这2种模式下无法保证onConnect/onClose/onReceive的顺序 1和3 客户端都需要recv
            'backlog'         => 128,//参数将决定最多同时有多少个等待accept的连接。
            'log_file'        => '/tmp/swoole.log',
            'task_worker_num' => 10,// task投递的速度，如每秒产生100个task 每个任务100ms 1s处理10个  100/10=10
        ));

        $serv->on('connect', function ($serv, $fd){

        });

        $serv->on('receive', function ($serv, $fd, $from_id, $data) {
            $serv->send($fd, 'Swoole: 收到数据');
            $task_id = $serv->task($data);

        });

        $serv->on('task',function (\swoole_server $serv, $task_id, $src_worker_id, $data){

            $log = json_decode($data, true);
            $appVersion     = !empty($log['data']['header']['app_version']) ? trim($log['data']['header']['app_version']):"unknow";
            $channel        = !empty($log['data']['header']['channel']) ? trim($log['data']['header']['channel']) : "unknow";
            $mainchannel    = !empty($log['header']['mainchannel']) ? trim($log['data']['header']['mainchannel']) : '';
            $uid            = $log['data']['header']['uid'];
            $launches       = $log['data']['body']['launch'];

            switch ($log['topic'])
            {
                case 'reader_wtzw':
                    $params = [
                        'appVersion' => $appVersion,
                        'channel'    => $channel,
                        'mainchannel'=> $mainchannel,
                        'uid'        => $uid,
                        'launches'   => $launches,
                        'project'    => 'reader_wtzw',
                    ];
                    //处理启动次数的进程
                    $this->dispatch(Launch::class, 'Index', $params);
                    break;

                default:
                    echo "unknow topic";
            }
            //处理业务
            $serv->finish("task完成任务");
        });

        $serv->on('finish',function(\swoole_server $serv, $task_id, $data){
            echo $data."\n";
        });

        $serv->on('close', function ($serv, $fd) {

        });

        $serv->start();
    }
}