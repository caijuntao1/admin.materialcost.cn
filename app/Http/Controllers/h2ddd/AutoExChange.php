<?php


namespace App\Http\Controllers\h2ddd;

use App\Http\Controllers\Controller;
use App\Jobs\AutoExChangeJobs;
use App\Models\h2ddd\UserModel;
use Illuminate\Support\Facades\Log;

class AutoExChange extends Controller
{
    public function autoExChange(){
        $records_user = UserModel::whereNotNull('password')
            ->whereRaw("(SELECT id FROM `h2ddd_user_exchange` where h2ddd_user.id = h2ddd_user_exchange.user_id and h2ddd_user_exchange.record = 1 and h2ddd_user_exchange.created_at between ".strtotime(date('Y-m-d 00:00:00'))." and ".strtotime(date('Y-m-d 23:59:59')).") is null")
            ->where('h2ddd_user.is_auto',1)
            ->get()
            ->toArray();
        if(empty($records_user)){
            Log::info('没有需要自动兑换的用户');
            return false;
        }
        AutoExChangeJobs::dispatch($records_user)->onQueue('h2ddd-autoExChange');
        return true;
    }
}
