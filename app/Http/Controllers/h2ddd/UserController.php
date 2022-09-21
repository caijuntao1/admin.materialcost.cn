<?php


namespace App\Http\Controllers\h2ddd;


use App\Http\Controllers\Controller;
use App\Http\Controllers\Http;
use App\Models\h2ddd\UserModel;
use Illuminate\Http\Request;
use Validator;
use Illuminate\Support\Facades\Log;
use GuzzleHttp;

class UserController extends Controller
{
    public function exChangeSteps(Request $request){
        $request_data = $request->all();
        $validate = Validator::make($request_data,[
            'phone' => 'required',
            'password' => 'required',
            'steps' => 'required',
        ],[
            'phone.required' => '请输入电话号码',
            'password.required' => '请输入密码',
            'steps.required' => '请输入兑换步数',
        ]);
        if ($validate->fails()) {
            return response()->json(['code' => 201, 'msg' => '缺少必填参数或参数不对', 'data' => $validate->errors()->toArray()]);
        }

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
            if($result['code'] == 1){
                //success
                return response()->json(['code' => 200, 'msg' => $result['msg'], 'data' => $result['data']]);
            }else{
                return response()->json(['code' => 201, 'msg' => $result['msg'], 'data' => $result['data']]);
            }
        }else{
            return response()->json(['code' => 201, 'msg' => $result_msg, 'data' => (object)[]]);
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
        $http = new Http();
        $response = $http->post($url, $param);
        $result = json_decode($response['data'], true);
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
    }
}
