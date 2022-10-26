<?php

namespace App\Console;

use App\Http\Controllers\h2ddd\AutoExChange;
use App\Http\Controllers\testApi\testApiController;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Log;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->call(function () {
            Log::info("每日定时兑换步数");
            AutoExChange::autoExChange();
        })->dailyAt('12:00');
        $schedule->call(function () {
            Log::info("每五分钟定时监测网站状态");
            //香不香港测试环境
            testApiController::HKOKTESTSERVER();
            //香不香港正式环境
            testApiController::HKOKSERVER();
            //留学鸟测试环境
            testApiController::LXBIRDTESTSERVER();
            //留学鸟正式环境
            testApiController::LXBIRDSERVER();
        })->everyFiveMinutes();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
