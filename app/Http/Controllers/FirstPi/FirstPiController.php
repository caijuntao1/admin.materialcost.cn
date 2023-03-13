<?php

namespace App\Http\Controllers\FirstPi;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Http;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FirstPiController extends Controller{
    public function FirstPiLogin(){
        $url = "https://h5.firstpi.cn/index.php?s=/api/user/login";
        $param = [
            'from'      => 'h5',
            'is_wx'     => 0,
            'nickName'  => '13691775300',
            'wxapp_id'  => '10001',
            'loginType' => 1,
            'password'  => 'Zhanghui',
            'openid'    => '',
            'token'     => '',
            'is_h5'     => 1
        ];
        $http = new Http();
        $response = $http->post($url, $param);
        $result = json_decode($response['data'], true);
        return $result;
    }
    public static function getFirstPiAPIData($page = 1,$spec_sku_id = ''){
        $login_data = self::FirstPiLogin();
        if(!empty($login_data) && $login_data['code'] == 1){
            $token = $login_data['data']['token'];
        }else{
            return array();
        }
        $method = "GET";
        $token = "&token=".$token;
        $querys = "s=/api/ordersale/salelists&page={$page}&order_by=sale_price&sort_by=asc&spec_sku_id={$spec_sku_id}&wxapp_id=10001&is_h5=1";
        $url = "https://h5.firstpi.cn/index.php?".$querys.$token;
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array());
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($curl);
        curl_close($curl);
        $result =  json_decode($result, true);
        return $result['data'];
    }
    public function updateAllData(Request $request){
        $request_data = $request->all();
        $search = $request_data['search'] ?? '';
        $get_one = $request_data['get_one'] ?? 0;
        if($search){
            $record = DB::table('firstpi_category')
                ->where(function ($query)use($search){
                    $query->OrWhere('spec_sku_id',$search)
                        ->OrWhere('spec_value',$search);
                })
                ->first();
            if(!$record){
                return response()->json(['code' => 201, 'msg' => '查找不到该产品', 'data' => (object)[]]);
            }
            $search = $record->spec_sku_id;
        }
        self::updateGoods($search,$get_one);
        return response()->json(['code' => 200, 'msg' => '已成功更新数据', 'data' => (object)[]]);
    }
    private static function updateGoods($spec_sku_id = 0,$get_one = 0){
        set_time_limit(0);
        //将30分钟前数据删除
        DB::table('firstpi_goods')
            ->where('updated_at','<=',strtotime('-30 minutes'))
            ->when($spec_sku_id,function ($query) use($spec_sku_id){
                $query->where('spec_sku_id',$spec_sku_id);
            })
            ->update(['deleted_at'=>time()]);
        $records_existed = DB::table('firstpi_goods')
            ->whereNull('deleted_at')
            ->when($spec_sku_id,function ($query) use($spec_sku_id){
                $query->where('spec_sku_id',$spec_sku_id);
            })
            ->select('order_id')
            ->get()
            ->toArray();
        $existed_ids = array_column($records_existed,'order_id');
        $request_data = self::getFirstPiAPIData(1,$spec_sku_id);
        if($get_one == 1){
            $last_page = 1;
        }else{
            $last_page = $request_data['last_page'];
        }
        $now = time();
        $i = 0;
        Log::info('更新首派数据开始');
        do{
            if(time() >= ($now + (2 * $i))){
                $insert_datas = [];
                $current_result = self::getFirstPiAPIData(($i+1),$spec_sku_id);
                Log::info(json_encode($current_result));
                $current_data = $current_result['data'];
                if(!empty($current_data))foreach($current_data as $index => $record){
                    $update_data = [
                        "order_id"=> $record['id'],
                        "origin_order_id"=> $record['origin_order_id'],
                        "shard_id"=> $record['shard_id'],
                        "is_blind_box"=> $record['is_blind_box'],
                        "sort_no"=> $record['sort_no'],
                        "spec_sku_id"=> $record['spec_sku_id'],
                        "spec_value"=> $record['spec_value'],
                        "sale_price"=> $record['sale_price'],
                        "buyer_user_id"=> $record['buyer_user_id'],
                        "status"=> $record['status']['value'],
                        "image_id"=> $record['image_id'],
                        "goods_id"=> $record['goods_id'],
                        "goods_price"=> $record['goods_price'],
                        "total_num"=> $record['total_num'],
                        "goods_image"=> $record['goods_image'],
                        "goods_name"=> $record['goods_name'],
                        "total_sort_no"=> $record['total_sort_no'],
                        "can_buy"=> $record['can_buy'],
                        "updated_at"=> time(),
                    ];
                    if(in_array($record['id'],$existed_ids)){
                        //update
                        $success = DB::table('firstpi_goods')->where('order_id',$record['id'])->update($update_data);
                    }else{
                        //insert
                        $update_data["created_at"] = time();
                        $insert_datas[] = $update_data;
                    }
                }
                if(!empty($insert_datas)){
                    $success = DB::table('firstpi_goods')->insert($insert_datas);
                }
                $i++;
            }
        }while($i < $last_page);
        Log::info('更新首派数据结束');
        return true;
    }
    public function getList(Request $request){
        $request_data = $request->all();
        $search = $request_data['search'] ?? '';
        $records = DB::table('firstpi_goods')
            ->when($search,function ($query)use($search){
                $query->OrWhere('spec_sku_id',$search)
                    ->OrWhere('spec_value',$search);
            })
            ->orderBy('sale_price','ASC')
            ->get()
            ->toArray();
        return response()->json(['code' => 200, 'msg' => '获取成功', 'data' => $records]);
    }
    public function getCategoryList(Request $request){
        $request_data = $request->all();
        $search = $request_data['search'] ?? '';
        $records = DB::table('firstpi_category')
            ->when($search,function ($query)use($search){
                $query->OrWhere('spec_sku_id',$search)
                    ->OrWhere('spec_value',$search);
            })
            ->select('spec_sku_id','spec_value','total_sort_no')
            ->selectRaw("
                ( SELECT sale_price FROM `firstpi_goods` WHERE firstpi_goods.spec_sku_id = firstpi_category.spec_sku_id and deleted_at is null ORDER BY sale_price ASC LIMIT 1 ) as min_sale_price,
                ( SELECT sale_price FROM `firstpi_goods` WHERE firstpi_goods.spec_sku_id = firstpi_category.spec_sku_id and deleted_at is null ORDER BY sale_price DESC LIMIT 1 ) as max_sale_price,
                ( SELECT count(id) FROM `firstpi_goods` WHERE firstpi_goods.spec_sku_id = firstpi_category.spec_sku_id and deleted_at is null LIMIT 1 ) as max_sale_total
            ")
            ->get()
            ->toArray();
        return response()->json(['code' => 200, 'msg' => '获取成功', 'data' => $records]);
    }
}
