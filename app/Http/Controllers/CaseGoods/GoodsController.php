<?php


namespace App\Http\Controllers\CaseGoods;


use App\Http\Controllers\Controller;
use App\Models\CaseGoods\CaseGoodsModel;
use Illuminate\Http\Request;
use Validator;
use Illuminate\Support\Facades\Storage;

class GoodsController extends Controller
{
    const LIMIT = 15;
    function strReplace($num = 0){
        $data = str_split($num);
        krsort($data) ;
        $str = '';
        $k = 0;
        for($i = (count($data))-1;$i >= 0;$i--){
            if($k % 3 == 0 && $k != 0){$str = ','.$str;}
            $str = strval($data[$i]) .$str;
            $k++;
        }
        return $str;
    }
    public function getGoodsList(Request $request){
        $str = $this->strReplace(123456789321540);
        $str = $this->strReplace(12345678999999015);

    var_dump($str);

        var_dump(date('Y-m-d',strtotime('-1 month -1 day') ));
        exit;
        $request_data = $request->all();
        $page = empty($request_data['page']) ? 1 : trim($request_data['page']);
        $search_data = [];
        //遍历筛选条件
        foreach($request_data as $key => $value){
            if(!empty($value)){
                switch ($key){
                    case 'sort_name':
                    case 'sort_by':
                    case 'keywords':
                    case 'goods_model_id':
                        $search_data[$key] = $value;
                        break;
                }
            }
        }
        $return_data = CaseGoodsModel::getList($search_data,$request_data['limit'] ?? self::LIMIT,$page);
        return response()->json(['code' => 200, 'msg' => 'success', 'data' => $return_data]);
    }
    public function saveGoodsDetail(Request $request){
        $request_data = $request->all();
        $validate = Validator::make($request_data,[
            'name' => 'required',
            'status' => 'required',
            'goods_model_id' => 'required',
        ],[
            'name.required' => '请输入商品标题',
            'status.required' => '请选择商品当前状态',
            'goods_model_id.required' => '请选择商品型号',
        ]);
        if ($validate->fails()) {
            return response()->json(['code' => 201, 'msg' => current($validate->errors()->toArray())[0], 'data' => $validate->errors()->toArray()]);
        }
        $id = $request_data['id'] ?? 0;
        if(!$id && !$request->hasFile('file')){
            return response()->json(['code' => 201, 'msg' => '请上传图片', 'data' => (object)[]]);
        }
        try {
            $save_data = [];
            $save_data['updated_at'] = time();
            $save_data['name'] = $request_data['name'];
            $save_data['status'] = $request_data['status'];
            $save_data['price'] = $request_data['price'] ?? 0;
            $save_data['goods_model_id'] = $request_data['goods_model_id'];
            if($request->hasFile('file')){
                $avatar = $request->file('file')->store('/public/images/CaseGoods');
                $avatar = Storage::url($avatar);
                $save_data['url'] = asset($avatar);
            }
            if(!$id){
                //新增
                $save_data['created_at'] = time();
                $id = CaseGoodsModel::insertGetId($save_data);
                if(!$id){
                    throw new Exception('创建失败!');
                }
            }else{
                //修改
                $success = CaseGoodsModel::where('id',$id)
                    ->update($save_data);
                if(!$success){
                    throw new Exception('更新失败!');
                }
            }
            return response()->json(['code' => 200, 'msg' => 'success', 'data' => (object)[]]);
        }catch (\ Exception $exception){
            return response()->json(['code' => 201, 'msg' => $exception->getMessage(), 'data' => (object)[]]);
        }
    }
    public static function random($length = 16)
    {
        $string = '';

        while (($len = strlen($string)) < $length) {
            $size = $length - $len;

            $bytes = random_bytes($size);

            $string .= substr(str_replace(['/', '+', '='], '', base64_encode($bytes)), 0, $size);
        }

        return $string;
    }
    public static function uploadImage(Request $request){
        if($request->hasFile('file')){
            $avatar = $request->file('file')->store('/public/images/CaseGoods');
            $avatar = Storage::url($avatar);
            return response()->json(['code' => 200, 'msg' => 'success', 'data' => ['url'=>asset($avatar)]]);
        }else{
            return response()->json(['code' => 201, 'msg' => '上传失败', 'data' => (object)[]]);
        }
    }
}
