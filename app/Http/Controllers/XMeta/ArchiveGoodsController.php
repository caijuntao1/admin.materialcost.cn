<?php


namespace App\Http\Controllers\XMeta;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use GuzzleHttp;

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
            $goods_count = count($records_goods);
            $goods_total = array_sum(array_column($records_goods,'goodsPrice'));
            $all_total += $goods_total;
            echo ($item['name'].'已售出:'.$goods_count.'份,合计:'.$goods_total.'元');
            echo ('<br>');
        }
        echo (date('Y-m-d H:i:s').'总计卖出:'.$all_total.'元');
    }
}
