<?php

namespace App\Console\Commands;

use GatewayWorker\BusinessWorker;
use GatewayWorker\Gateway;
use GatewayWorker\Register;
use Illuminate\Console\Command;
use Workerman\Worker;

class WorkermanCommand extends Command
{

    protected $signature = 'workman {action} {--d}'; //执行该命令的方式

    protected $description = 'Start a Workerman server.';

    public function handle()
    {
        global $argv;
        $action = $this->argument('action');

        $argv[0] = 'wk';
        $argv[1] = $action;
        $argv[2] = $this->option('d') ? '-d' : '';
        // -d守护模式，不会因为关闭系统命令页面而被杀掉进程。 没有-d则关闭命令页面直接退出进程
        $this->start();
    }



    private function start()
    {
        $this->startGateWay();
        $this->startBusinessWorker();
        $this->startRegister();
        Worker::runAll();
    }

    private function startBusinessWorker()
    {
        $worker                  = new BusinessWorker();
        $worker->name            = 'BusinessWorker';
        $worker->count           = 1;
        $worker->registerAddress = '127.0.0.1:1236';
        $worker->eventHandler    = \App\Events::class; //用作监听事件的文件
    }

    private function startGateWay()
    {
    //因为小程序等一些平台，要求使用wss进行socket,所以，这里需要配置下wss
    //此处的cert.pem和key.key是域名的证书文件
        $content = array(
            'ssl' => array(
                'local_cert' => public_path('cert.pem'),
                'local_pk' => public_path('key.key'),
                'verify_peer' => false
            )
        );
        $gateway = new Gateway("websocket://0.0.0.0:2346", $content);
        //如果不需要wss，则不用加入content这个参数
        $gateway->transport            = 'ssl';//不需要wss，也不用加入这个参数。
        $gateway->name                 = 'Gateway';
        $gateway->count                = 1;
        $gateway->lanIp                = '127.0.0.1';
        $gateway->startPort            = 2300;
        $gateway->pingInterval         = 30;
        $gateway->pingNotResponseLimit = 0;
        $data = array(
            'type' => 'heart'
        );
        $gateway->pingData = json_encode($data, true);
        $gateway->registerAddress      = '127.0.0.1:1236';
    }

    private function startRegister()
    {
        new Register('text://0.0.0.0:1236');
    }
}
