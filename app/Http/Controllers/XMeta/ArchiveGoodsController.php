<?php


namespace App\Http\Controllers\XMeta;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use GuzzleHttp;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ArchiveGoodsController extends Controller
{
    public function updateGoods(){
        $goods_array = [
            [
                'name'      => '八宝金莲',
                'archiveId' => '6917',
                'platformId'=> '573',
            ],
            [
                'name'      => '鼠王',
                'archiveId' => '6909',
                'platformId'=> '573',
            ],
            [
                'name'      => '比干',
                'archiveId' => '6910',
                'platformId'=> '573',
            ],
            [
                'name'      => '氢动八蛇创世勋章',
                'archiveId' => '6918',
                'platformId'=> '573',
            ],
            [
                'name'      => '八蛇终极勋章',
                'archiveId' => '6857',
                'platformId'=> '573',
            ],
            [
                'name'      => '探索者',
                'archiveId' => '6911',
                'platformId'=> '573',
            ],
            [
                'name'      => '李斯',
                'archiveId' => '6919',
                'platformId'=> '573',
            ],
            [
                'name'      => '天蓬元帅',
                'archiveId' => '6916',
                'platformId'=> '573',
            ],
            [
                'name'      => '流光运动鞋',
                'archiveId' => '6920',
                'platformId'=> '573',
            ],
            [
                'name'      => '氢动25',
                'archiveId' => '6912',
                'platformId'=> '573',
            ]
        ];
//        $goods_array = [
//            [
//                'name'      => '探索者',
//                'archiveId' => '6911',
//                'platformId'=> '573',
//            ]
//        ];
        $all_total = 0;
        $all_count = 0;
        $statistics_id = DB::table('xmeta_statistics')->insertGetId([
            'total_price'   => 0,
            'total_qty'   => 0,
            'created_at'   => time(),
            'updated_at'   => time(),
            'data_json'   =>'',
        ]);
        foreach($goods_array as $item){
            $url = "https://api.x-metash.com/api/prod/NFTMall/h5/goods/archiveGoods";
            $client = new GuzzleHttp\Client;
            $param = [
                'archiveId' => $item['archiveId'],
                'platformId' => $item['platformId'],
                'page' => 1,
                'pageSize' => 1000,
                'priceSort' => 1,
                'sellStatus' => 2,
            ];
            $headers = array(
                'Accept' => 'application/json',
                'content-type' => 'application/json',
                'Accept-Charset' => 'utf-8'
            );
            $request = $client->post($url,
                [
                    'headers'=>$headers,
                    'body'=>json_encode($param, JSON_UNESCAPED_SLASHES)
                ]
            );
            $result = json_decode( $request->getBody(), true);
            $records_goods = $result['data']['goodsArchiveList'] ?? [];
            if(count($records_goods) > 0){
                $insert_data = [];
                foreach($records_goods as $record_goods){
                    $insert_data[] = [
                        'statistics_id' => $statistics_id,
                        'platformId'    => $item['platformId'],
                        'archiveId'     => $item['archiveId'],
                        'goodsId'       => $record_goods['goodsId'],
                        'goodsName'     => $record_goods['goodsName'],
                        'goodsNo'       => $record_goods['goodsNo'],
                        'goodsPrice'    => $record_goods['goodsPrice'],
                        'sellStatus'    => $record_goods['sellStatus'],
                        'sellTime'      => $record_goods['sellTime'],
                        'created_at'    => time(),
                        'updated_at'    => time(),
                    ];
                }
                DB::table('xmeta_goods')->insert($insert_data);
            }
            $goods_count = count($records_goods);
            $goods_total = array_sum(array_column($records_goods,'goodsPrice'));
            $all_total += $goods_total;
            $all_count += $goods_count;
            echo ($item['name'].'已售出:'.$goods_count.'份,合计:'.$goods_total.'元');
            echo ('<br>');
        }
        $cache_key = 'h2ddd_goods_last_updatetime';
        $cache_key2 = 'h2ddd_goods_last_salestotal';
        $nowTime = time();
        echo ('当前查询'.date('Y-m-d H:i:s',$nowTime).'总计卖出:'.$all_total.'元;');
        if(Cache::has($cache_key)){
            echo ('<br>');
            echo ('上一期查询'.date('Y-m-d H:i:s',Cache::get($cache_key)).'总计卖出:'.Cache::get($cache_key2).'元;');
            echo ('<br>');
            echo ('相隔近'.ceil(($nowTime-Cache::get($cache_key))/60).'分钟共计卖出'.($all_total-Cache::get($cache_key2)).'元;');
        }
        DB::table('xmeta_statistics')->where('id',$statistics_id)->update([
            'total_price' => $all_total,
            'total_qty' => $all_count,
        ]);
        Cache::put($cache_key,$nowTime);
        Cache::put($cache_key2,$all_total);
    }
}