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
    protected $platformId;
    public function __construct(){
        $this->platformId = 573;
    }
    public function getGoodsArray(){
        $url = "https://api.x-metash.com/api/prod/NFTMall/h5/home/archive";
        $client = new GuzzleHttp\Client;
        $param = [
            'isTransfer' => 1,
            'pageNum' => 1,
            'pageSize' => 100,
            'platformId' => $this->platformId,
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
        $records_goods = $result['data'];
        $return_data = [];
        foreach($records_goods as $record_goods){
            $return_data[] = [
                'name'          => $record_goods['archiveName'],
                'archiveId'     => $record_goods['id'],
                'platformId'    => $record_goods['platformId']
            ];
        }
        return $return_data;
    }
    public function updateGoods(){
        $goods_array = $this->getGoodsArray();
        $all_total = 0;
        $all_count = 0;
        $statistics_id = DB::table('xmeta_statistics')->insertGetId([
            'total_price'   => 0,
            'total_qty'   => 0,
            'created_at'   => time(),
            'updated_at'   => time(),
            'data_json'   =>'',
            'platformId' => $this->platformId
        ]);
        $records_category = $this->getMinPriceList();
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
                        'timePass'      => $record_goods['timePass'],
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
            echo ($item['name'].'已售出:'.$goods_count.'份,合计:'.$goods_total.'元;');
            foreach($records_category as $record_category){
                if($record_category['id'] == $item['archiveId']){
                    echo ('当前寄售最低价:'.$record_category['goodsMinPrice']);
                }
            }
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
    public function getMinPriceList(){
        $url = "https://api.x-metash.com/api/prod/NFTMall/h5/home/archive";
        $client = new GuzzleHttp\Client;
        $param = [
            'isTransfer' => 1,
            'pageNum' => 1,
            'pageSize' => 100,
            'platformId' => $this->platformId,
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
        $records_goods = $result['data'];
        return $records_goods;
        echo ('-----------------------------');echo ('<br>');
        foreach($records_goods as $record_goods){
            echo ($record_goods['archiveName'].'售卖中最低价:'.$record_goods['goodsMinPrice']);
            echo ('<br>');
        }
    }
    public function getDetail(){
        $statistics_id = DB::table('xmeta_statistics')->where('platformId',$this->platformId)->max('id');
        $goods_array = $this->getGoodsArray();
        foreach($goods_array as &$goods){
            //最高数据
            $record_max = DB::table('xmeta_goods')
                ->where('archiveId',$goods['archiveId'])
                ->where('statistics_id',$statistics_id)
                ->orderBy('goodsPrice','DESC')
                ->first();
            $goods['max_price'] = $record_max->goodsPrice ?? '0.00';
            $goods['max_time'] = !empty($record_max->sellTime) ? date('Y-m-d',strtotime($record_max->sellTime)) : '-';
            $goods['max_no'] = $record_max->goodsNo ?? '-';
            //最低数据
            $record_min = DB::table('xmeta_goods')
                ->where('archiveId',$goods['archiveId'])
                ->where('statistics_id',$statistics_id)
                ->orderBy('goodsPrice','ASC')
                ->first();
            $goods['min_price'] = $record_min->goodsPrice ?? '0.00';
            $goods['min_time'] = !empty($record_min->sellTime) ? date('Y-m-d',strtotime($record_min->sellTime)) : '-';
            $goods['min_no'] = $record_min->goodsNo ?? '-';
            //最新数据
            $record_new = DB::table('xmeta_goods')
                ->where('archiveId',$goods['archiveId'])
                ->where('statistics_id',$statistics_id)
                ->orderBy('sellTime','DESC')
                ->first();
            $goods['new_price'] = $record_new->goodsPrice ?? '0.00';
            $goods['new_time'] = !empty($record_new->sellTime) ? date('Y-m-d',strtotime($record_new->sellTime)) : '-';
            $goods['new_no'] = $record_new->goodsNo ?? '-';
            //合计
            $count = DB::table('xmeta_goods')
                ->where('archiveId',$goods['archiveId'])
                ->where('statistics_id',$statistics_id)
                ->COUNT();
            $goods['count'] = $count;
            $total = DB::table('xmeta_goods')
                ->where('archiveId',$goods['archiveId'])
                ->where('statistics_id',$statistics_id)
                ->SUM('goodsPrice');
            $goods['total'] = $total;
        }
        return response()->json([
            'code'  => 200,
            'data'  => $goods_array,
            'msg'   => 'success'
        ]);
    }
}
