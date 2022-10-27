<?php

namespace App\Jobs;

use App\Http\Controllers\h2ddd\UserController;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class AutoExChangeJobs implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $records_user;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($records_user)
    {
        $this->records_user = $records_user;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        //
        $records_user = $this->records_user;
        self::autoExChange($records_user);
    }

    public static function autoExChange($records_user){
        Log::info('开始自动步数兑换积分');
        //发送小程序订阅消息
        foreach($records_user as $record_user){
            Log::info('user_id:'.$record_user['id']);
            $UserController = new UserController();
            $result = $UserController->exChange($record_user['phone'],$record_user['password']);
            Log::info('自动步数兑换积分结果:' . json_encode($result));
//            if(!empty($result) && $result['code'] == 200){
//                $result = $UserController->lottery($record_user['token']);
//                Log::info('自动抽奖结果:' . json_encode($result));
//            }
        }
        Log::info('自动步数兑换积分结束');
    }
}
