<?php


namespace App\Models\Administrator;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Exception;

class UsersModel extends Model
{
    protected $table = "users";

    public static function getUserInfo($username = '',$password = ''){
        if(!$username){
            return array(false,null,'请传入邮箱地址/手机号');
        }elseif(!$password){
            return array(false,null,'请传入密码');
        }
        try {
            $record = self::where(function ($query) use($username){
                $query->where('email',$username)
                    ->OrWhere('phone',$username);
            })
                ->first();
            if(!empty($record)){
                if(Hash::check($password,$record->password)){
                    return array(true,$record,'success');
                }else{
                    throw new Exception('密码不匹配');
                }
            }else{
                throw new Exception('查找不到该用户');
            }
        }catch (\Exception $exception){
            return array(false,null,$exception->getMessage());
        }
    }
}
