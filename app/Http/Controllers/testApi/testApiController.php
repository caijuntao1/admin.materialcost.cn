<?php


namespace App\Http\Controllers\testApi;


use App\Http\Controllers\Controller;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Exception;
use Illuminate\Support\Facades\Cache;

class testApiController extends Controller
{
    public static function HKOKTESTSERVER(){
        $title = '香不香港测试环境';
        $url = "https://dev.uniweb.offerhk.com/api/v1/AppStoreReview/appstore";
        $cache_key = 'hkok_test_access_failed';
        self::requestApi($title,$url,$cache_key);
    }
    public static function HKOKSERVER(){
        $title = '香不香港正式环境';
        $url = "https://uniweb.offerhk.com/api/v1/AppStoreReview/appstore";
        $cache_key = 'hkok_access_failed';
        self::requestApi($title,$url,$cache_key);
    }
    public static function LXBIRDTESTSERVER(){
        $title = '留学鸟测试环境';
        $url = "http://devapi.lxbird.com/api/school/arealist";
        $cache_key = 'lxbird_test_access_failed';
        self::requestApi($title,$url,$cache_key);
    }
    public static function LXBIRDSERVER(){
        $title = '留学鸟正式环境';
        $url = "https://api.lxbird.com/api/school/arealist";
        $cache_key = 'lxbird_access_failed';
        self::requestApi($title,$url,$cache_key);
    }
    public static function pingHKOKApi(){
        $title = '香不香港测试环境';
        $url = "https://dev.uniweb.offerhk.com/api/v1/AppStoreReview/appstore";
        $cache_key = 'hkok_test_access_failed';
        self::requestApi($title,$url,$cache_key);
    }
    public static function requestApi($title = '',$url = '',$cache_key = ''){
        try {
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
            if (!empty($header)) {
                curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
            }
            if (!empty($data)){
                curl_setopt($curl, CURLOPT_POST, 1);
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            }
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
            $response = curl_exec($curl);
            curl_close($curl);
            $response = json_decode($response,true);
            if(empty($response) || $response['code'] != 200){
                throw new Exception('访问失败');
            }
            Log::info($title.'目前运行正常:');
            if (Cache::has($cache_key)) {
                Cache::forget($cache_key);
            }
            return true;
        }catch (\Exception $exception){
            $value = Cache::get($cache_key);
            if(!empty($value)){
                //已存在提醒,超过十分钟再次提醒
                $diff = intval((time()-$value) / 60);
                if($diff >= 10){
                    //重置缓存值
                    Cache::forever($cache_key, time());
                    $content = $title.'访问失败已超过了'.$diff.'分钟,请火速处理!!';
                }else{
                    Log::info($title.'访问失败已超过了'.$diff.'分钟');
                    return false;
                }
            }else{
                //第一次提醒
                Cache::forever($cache_key, time());
                $content = $title.'访问失败,请尽快处理!!';
            }
            //访问错误,提醒管理员
            if(in_array($title,['留学鸟正式环境','留学鸟测试环境'])){
                $key = '7ad2dfb222359a3096597279104d9a486c255f5f02b63ac8cd3acf81698984a7';
            }else{
                $key = '1f818d945f5a856bb224440148940392b486fa029c40e084505b580a4fb6ca87';
            }
            $response = self::sendMsg($key,$content);
            $result = json_decode($response,true);
            if($result['errcode'] == 0){
                Log::info($title.'访问失败发送钉钉提醒成功');
            }else{
                Log::info($title.'访问失败发送钉钉提醒失败:'.$result['errmsg']);
            }
            Log::info($title.'访问失败:'.$exception->getMessage());
            return true;
        }
    }
    /*
     * 发送信息到钉钉群
     */
    public static function sendMsg($key = '1f818d945f5a856bb224440148940392b486fa029c40e084505b580a4fb6ca87', $content, $atAll = true)
    {
        //拼接机器人接口url
        $url = "https://oapi.dingtalk.com/robot/send?access_token=" . $key;
        //发送
        $header = array('Content-Type: application/json;charset=utf-8');
        $data = json_encode(array(
            'msgtype' => "text",
            "text" => array("content" => $content),
            "at" => array("atMobiles" => [],
                "isAtAll" => $atAll,
            )
        ));
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        if ($header) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        }
        if (!empty($data)){
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
        $response = curl_exec($curl);
        curl_close($curl);
        Log::info('发送钉钉提醒结果：' . json_encode($response));
        return $response;
    }
}
