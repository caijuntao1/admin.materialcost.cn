<?php


namespace App\Http\Controllers\h2ddd;

use App\Http\Controllers\Controller;
use App\Models\h2ddd\UserModel;
use Illuminate\Support\Facades\Log;
use GuzzleHttp;

class AutoExChange extends Controller
{
    public function autoExChange(){
        $records_user = UserModel::whereNotNull('password')->get()->toArray();
        if(!empty($records_user)){
            foreach($records_user as $record_user){
                $steps = 6000;
                $phone = $record_user['phone'];
                $password = $record_user['password'];
                list($result_code,$result_msg,$result_data) = UserController::login($phone,$password);
                if($result_code == true && $result_data['token']){
                    $url = "https://www.h2ddd.com/api/steps/exchange?steps=".$steps;
                    $client = new GuzzleHttp\Client;
                    $response = $client->request('GET', $url, [
                        'headers' => ['token' => $result_data['token']],
                        'verify' => false
                    ]);
                    $result = json_decode( $response->getBody(), true);
                    if($result['code'] == 1){
                        //success
                        Log::info('phone:'.$phone.'兑换成功:'.json_encode($result));
                        //每日一次的免费抽奖
                        $url = "https://www.h2ddd.com/api/luck_draw/play?draw_id=1";
                        $client = new GuzzleHttp\Client;
                        $response = $client->request('GET', $url, [
                            'headers' => ['token' => $result_data['token']],
                            'verify' => false
                        ]);
                        $result = json_decode( $response->getBody(), true);
                        Log::info('phone:'.$phone.'抽奖成功:'.$result);
                    }else{
                        Log::info('phone:'.$phone.'兑换失败:'.$result['msg']);
                    }
                }else{
                    Log::info('phone:'.$phone.'登录失败:'.$result_msg);
                }
            }
            return true;
        }
        return false;
    }
}
