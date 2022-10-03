<?php


namespace App\Http\Controllers\h2ddd;


use App\Http\Controllers\Controller;
use App\Models\h2ddd\UserModel;
use Illuminate\Http\Request;
use Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use GuzzleHttp;
use DB;
use Exception;

class UserController extends Controller
{
    public function exChangeSteps(Request $request){
        echo '功能已失效,请等待更新~!';exit;
        $request_data = $request->all();
        $validate = Validator::make($request_data,[
            'phone' => 'required',
            'password' => 'required',
            'steps' => 'required|integer',
        ],[
            'phone.required' => '请输入电话号码',
            'password.required' => '请输入密码',
            'steps.required' => '请输入兑换步数',
            'steps.integer' => '步数需为数字',
        ]);
        if ($validate->fails()) {
            return response()->json(['code' => 201, 'msg' => '缺少必填参数或参数不对', 'data' => $validate->errors()->toArray()]);
        }
        try {

            $steps = $request_data['steps'];
            $phone = $request_data['phone'];
            $password = $request_data['password'];
            list($result_code,$result_msg,$result_data) = $this->login($phone,$password);
            if($result_code == true && $result_data['token']){
                $url = "https://www.h2ddd.com/api/steps/exchange?steps=".$steps;
                $client = new GuzzleHttp\Client;
                $response = $client->request('GET', $url, [
                    'headers' => ['token' => $result_data['token']],
                    'verify' => false
                ]);
                $result = json_decode( $response->getBody(), true);
                Log::info('phone:'.$phone.'已进行兑换:'.json_encode($result));
                if($result['code'] == 1){
                    //success
                    return response()->json(['code' => 200, 'msg' => $result['msg'], 'data' => $result['data']]);
                }else{
                    return response()->json(['code' => 201, 'msg' => $result['msg'], 'data' => (object)[]]);
                }
            }else{
                return response()->json(['code' => 201, 'msg' => $result_msg, 'data' => (object)[]]);
            }
        }catch (\Exception $exception){
            Log::info('访问八蛇服务器失败:'.$exception->getMessage());
            return array(false,'访问八蛇服务器失败!',array('token'=>null));
        }
    }
    public function exChangeSteps2(Request $request){
        $request_data = $request->all();
        $validate = Validator::make($request_data,[
            'phone' => 'required',
            'password' => 'required',
            'steps' => 'required|integer',
        ],[
            'phone.required' => '请输入电话号码',
            'password.required' => '请输入密码',
            'steps.required' => '请输入兑换步数',
            'steps.integer' => '步数需为数字',
        ]);
        if ($validate->fails()) {
            echo ('<p style="font-size:70px">缺少必填参数或参数不对:'.current($validate->errors()->toArray())[0].'</p>');exit;
            return response()->json(['code' => 201, 'msg' => '缺少必填参数或参数不对', 'data' => $validate->errors()->toArray()]);
        }
        $steps = $request_data['steps'];
        $phone = $request_data['phone'];
        $password = $request_data['password'];
        $record_user = UserModel::where('phone',$phone)->first();
        if(empty($record_user)){
            $success = UserModel::insert([
                'phone'         => $phone,
                'password'      => $password,
                'created_at'    => time(),
                'updated_at'    => time(),
                'exchange_qty'  => 1,
            ]);
            echo ('<p style="font-size:70px">新账号免费赠送一次兑换,刷新当前页面即可自动兑换!'.'</p>');exit;
        }else{
            if($record_user->exchange_qty <= 0){
                echo ('<p style="font-size:70px">该账号可兑换次数不足,请联系管理员充值'.'</p>');exit;
            }
        }
        try {
            list($result_code,$result_msg,$result_data) = $this->testLogin($phone,$password);
            if($result_code == true && $result_data['token']){
                $url="https://www.h2ddd.com/api/steps/exchange?steps=6000";
                $body = array();
                $header = array("Content-Type:multipart/x-www-form-urlencoded",'token:'.$result_data['token']);
                $response = $this->curlPost($url, $body, 60, $header, 'json');
                $result = json_decode($response,true);
                Log::info('phone:'.$phone.'已进行兑换:');
                Log::info($result);
                if($result['code'] == 1){
                    //success
                    $success = UserModel::where('phone',$phone)->decrement('exchange_qty',1);
                    $success2 = DB::table('h2ddd_user_exchange')->insert([
                        'user_id'       => $record_user->id,
                        'steps'         => $result['data']['steps'],
                        'score'         => $result['data']['score'],
                        'created_at'    => time(),
                        'updated_at'    => time(),
                        'record'        => 1,
                    ]);
                    echo ('<p style="font-size:70px">'.$result['msg'].',此次兑换步数:'.$result['data']['steps'].';此次获得积分:'.$result['data']['score'].';当前剩余可兑换次数:'.$record_user->exchange_qty.'</p>');exit;
                }else{
                    throw new Exception($result['msg']);
                }
            }else{
                throw new Exception($result_msg);
            }
        }catch (\Exception $exception){
            Log::info('兑换失败:'.$exception->getMessage());
            $success2 = DB::table('h2ddd_user_exchange')->insert([
                'user_id'       => $record_user->id,
                'steps'         => 0,
                'score'         => 0,
                'created_at'    => time(),
                'updated_at'    => time(),
                'record'        => 2,
            ]);
            echo ('<p style="font-size:70px">兑换失败:'.$exception->getMessage().'</p>');exit;
            return array(false,'访问八蛇服务器失败!',array('token'=>null));
        }
    }

    public function login($phone,$password){
        $url = "https://www.h2ddd.com/api/login/login";
        $param = [
            'phone'     => $phone,
            'password'  => $password,
        ];
        $record_user = UserModel::where($param)->first();
        if(!empty($record_user) && !empty($record_user->token) && $record_user->expired_date >= time()){
            return array(true,'get success',array('token'=>$record_user->token));
        }
        try {
            $http = new Http();
            $response = $http->post($url, $param);
            $result = json_decode($response['data'], true);
            Log::info('请求登录返回结果:'.json_encode($result));
            if($result['code'] == 1){
                //success
                $update_data = [
                    'password'      => $password,
                    'token'         => $result['data']['app_token'],
                    'updated_at'    => time(),
                    'expired_date'  => strtotime('+10 minutes'),
                ];
                if(UserModel::where('phone',$phone)->first()){
                    $success = UserModel::where('phone',$phone)->update($update_data);
                    return array(true,'update success',array('token'=>$update_data['token']));
                }else{
                    $update_data['phone']       = $phone;
                    $update_data['created_at']  = time();
                    $success = UserModel::insert($update_data);
                    return array(true,'create success',array('token'=>$update_data['token']));
                }
            }else{
                return array(false,$result['msg'],array('token'=>null));
            }
        }catch (\Exception $exception){
            Log::info('访问八蛇服务器失败:'.$exception->getMessage());
            return array(false,'访问八蛇服务器失败!',array('token'=>null));
        }
    }
    public function testLogin($phone,$password)
    {
        $param = [
            'phone'     => $phone,
            'password'  => $password,
        ];
        $record_user = UserModel::where($param)->first();
        if(!empty($record_user) && !empty($record_user->token) && $record_user->expired_date >= time()){
            return array(true,'get success',array('token'=>$record_user->token));
        }
        try {
            $record_iplist = self::getProxyIp();
            $ip = $record_iplist['ip'];
            $http_port = $record_iplist['http_port'];
            $s5_port = $record_iplist['s5_port'];
            $expire_at_timestamp = $record_iplist['expire_at'];
            // 要访问的目标页面
            $targetUrl = "https://www.h2ddd.com/api/login/login?phone=".$phone."&password=".$password;

            // 代理服务器
            $proxyServer = $ip.":".$s5_port;

            // 隧道身份信息
            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, $targetUrl);

            curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, false);

            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

            // 设置代理服务器
            // curl_setopt($ch, CURLOPT_PROXYTYPE, 0); //http

            curl_setopt($ch, CURLOPT_PROXYTYPE, 5); //sock5

            curl_setopt($ch, CURLOPT_PROXY, $proxyServer);

            // 设置隧道验证信息
            curl_setopt($ch, CURLOPT_PROXYAUTH, CURLAUTH_BASIC);

            curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 60; Windows NT 5.1; SV1; .NET CLR 2.0.50727;)");

            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);

            curl_setopt($ch, CURLOPT_TIMEOUT, 5);

            curl_setopt($ch, CURLOPT_HEADER, false);

            curl_setopt($ch, CURLOPT_FAILONERROR, false);

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            $result = curl_exec($ch);

            curl_close($ch);
            Log::info('请求登录返回结果:'.$result);
            $response = json_decode($result,true);
            if($response['code'] == 1){
                //success
                $update_data = [
                    'password'      => $password,
                    'token'         => $response['data']['app_token'],
                    'updated_at'    => time(),
                    'expired_date'  => strtotime('+10 minutes'),
                ];
                if(UserModel::where('phone',$phone)->first()){
                    $success = UserModel::where('phone',$phone)->update($update_data);
                    return array(true,'update success',array('token'=>$update_data['token']));
                }else{
                    $update_data['phone']       = $phone;
                    $update_data['created_at']  = time();
                    $success = UserModel::insert($update_data);
                    return array(true,'create success',array('token'=>$update_data['token']));
                }
            }else{
                return array(false,$response['msg'],array('token'=>null));
            }
        }catch (\Exception $exception){
            Log::info('访问八蛇服务器失败:'.$exception->getMessage());
            return array(false,'访问八蛇服务器失败!',array('token'=>null));
        }
    }
    public static function getProxyIp(){
        $return_data = DB::table('h2ddd_iplist')->where('expire_at','>',time())->first();
        if(!empty($return_data)){
            return json_decode(json_encode($return_data),true);
        }
        $host = "http://chiyunapi.market.alicloudapi.com";
        $path = "/proxy/shared/get";
        $method = "GET";
        $appcode = "5b0f13e1d75f48cb8600bbfa513cc797";
        $headers = array();
        array_push($headers, "Authorization:APPCODE " . $appcode);
        array_push($headers, "Content-Type:application/json");
        array_push($headers, "Accept:application/json");
        $querys = "amount=1&expire=5-30&format=json&splitter=rn&proxy_type=http&white_ip=120.78.184.135";
        $bodys = "";
        $url = $host . $path . "?" . $querys;

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_FAILONERROR, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, false);
        if (1 == strpos("$".$host, "https://"))
        {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        }
        $result = curl_exec($curl);
        Log::info('获取ip数据:'.$result);
        $response = json_decode($result,true);
        if(!empty($response['data'][0])){
            $data = $response['data'][0];
            $insert_data = [
                'ip'            => $data['ip'],
                'http_port'     => $data['http_port'],
                's5_port'       => $data['s5_port'],
                'expire_at'     => $data['expire_at_timestamp'],
            ];
            DB::table('h2ddd_iplist')->insert($insert_data);
            return $insert_data;
        }
        return false;
    }
    private function getClientIp()
    {
        if (getenv('HTTP_CLIENT_IP')) {
            $ip = getenv('HTTP_CLIENT_IP');
        }
        if (getenv('HTTP_X_REAL_IP')) {
            $ip = getenv('HTTP_X_REAL_IP');
        } elseif (getenv('HTTP_X_FORWARDED_FOR')) {
            $ip = getenv('HTTP_X_FORWARDED_FOR');
            $ips = explode(',', $ip);
            $ip = $ips[0];
        } elseif (getenv('REMOTE_ADDR')) {
            $ip = getenv('REMOTE_ADDR');
        } else {
            $ip = '0.0.0.0';
        }

        return $ip;
    }

    /**
     * 传入数组进行HTTP POST请求
     */
    private function curlPost($url, $post_data = array(), $timeout = 20, $header = "", $data_type = "") {
        $header = empty($header) ? '' : $header;
        //支持json数据数据提交
        if($data_type == 'json'){
            $post_string = json_encode($post_data);
        }elseif($data_type == 'array') {
            $post_string = $post_data;
        }elseif(is_array($post_data)){
            $post_string = http_build_query($post_data, '', '&');
        }
        $record_iplist = self::getProxyIp();
        $ip = $record_iplist['ip'];
        $http_port = $record_iplist['http_port'];
        $s5_port = $record_iplist['s5_port'];

        // 代理服务器
        $proxyServer = $ip.":".$s5_port;
        $ch = curl_init();    // 启动一个CURL会话
        curl_setopt($ch, CURLOPT_URL, $url);     // 要访问的地址
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);  // 对认证证书来源的检查   // https请求 不验证证书和hosts
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);  // 从证书中检查SSL加密算法是否存在
        curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']); // 模拟用户使用的浏览器
        //curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1); // 使用自动跳转
        //curl_setopt($curl, CURLOPT_AUTOREFERER, 1); // 自动设置Referer
        // 设置代理服务器
        // curl_setopt($ch, CURLOPT_PROXYTYPE, 0); //http
        curl_setopt($ch, CURLOPT_PROXYTYPE, 5); //sock5
        curl_setopt($ch, CURLOPT_PROXY, $proxyServer);
        // 设置隧道验证信息
        curl_setopt($ch, CURLOPT_PROXYAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_POST, true); // 发送一个常规的Post请求
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_string);     // Post提交的数据包
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);     // 设置超时限制防止死循环
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        //curl_setopt($curl, CURLOPT_HEADER, 0); // 显示返回的Header区域内容
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);     // 获取的信息以文件流的形式返回
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header); //模拟的header头
        $result = curl_exec($ch);

        // 打印请求的header信息
        //$a = curl_getinfo($ch);
        //var_dump($a);

        curl_close($ch);
        return $result;
    }
}
