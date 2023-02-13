<?php


namespace App\Http\Controllers\AliOss;


use App\Http\Controllers\Controller;
use App\Service\AliossService;
use Illuminate\Http\Request;

class ServiceController extends Controller
{

    public function getOssProjectList(Request $request){
        $dir = $request->get('dir') ?? '';
        //获取文档
        $file_data = [];
        //先查找默认文件夹下的所有文件及文件夹
        $result = AliossService::fileList($dir);
        if(!empty($result['file'])){
            $file_data = array_merge($file_data,$result['file']);
        }
        $dir_data = $result['dir'];
        do {
            $tmp_data = [];
            foreach ($dir_data as $record){
                $result = AliossService::fileList($record['dir']);
                if(!empty($result['file'])){
                    $file_data = array_merge($file_data,$result['file']);
                }
                $tmp_data = array_merge($tmp_data,$result['dir']);
            }
            $dir_data = $tmp_data;
        } while (!empty($dir_data));
        $return_data = [
            'count' => 0,
            'total' => 0,
            'data'  => $file_data,
        ];
        foreach ($file_data as $index => $data){
            if(strtotime($data['update_at']) <= 1642521599 && $data['size'] > 0){
                //AliossService::deleteObject('offer-backup',$data['name']);
            }
        }
        $return_data['count'] = count($file_data);
        $return_data['total'] = round(array_sum(array_column($file_data,'size')) / 1024 / 1024 / 1024,2) . 'GB';
        return response()->json(array(
            "code"=> 200,
            "data"=> $return_data,
            "msg"=> "ok",
        ));
    }
}
