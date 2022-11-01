<?php


namespace App\Http\Controllers\Administrator;


use App\Http\Controllers\Controller;
use App\Models\Users;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use JWTAuth;

class UsersController extends Controller
{
    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {

        $data = $request->input();
        $username = $data['username'];
        $password = $data['password'];
        $user = Users::where(function ($query) use($username){
                $query->where('email',$username)
                    ->OrWhere('phone',$username);
            })
            ->first();
        if(!empty($user)){
            if(!Hash::check($password,$user->password)){
                return response()->json([
                    'code'  => 201,
                    'msg'   => '密码不匹配',
                    'data'  => null
                ]);
            }
        }else{
            return response()->json([
                'code'  => 201,
                'msg'   => '查找不到用户',
                'data'  => null
            ]);
        }
        //方式一
        $token = JWTAuth::fromUser($user);
        //方式二
//        $token = auth('api')->login($user);
        //方式三、下面这种方式必须使用密码登录
//        $token = auth('api')->attempt($user->toArray());
        if(!$token ){
            return response()->json(['error' => 'Unauthorized'],401);
        }
        return $this->respondWithToken($token,$user);
    }
    protected function respondWithToken($token, $data)
    {
        unset($data['password'],$data['password'],$data['password']);
        return response()->json([
            'code'  => 200,
            'msg'  => 'success',
            'data' => [
                'name'  => $data->name,
                'email'  => $data->email,
                'phone'  => $data->phone,
                'access_token' => 'bearer '.$token,
                'token_type' => 'bearer'
            ],
        ]);
    }

    public function test(){
        var_dump(12);
    }
}
