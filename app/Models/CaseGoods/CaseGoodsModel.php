<?php


namespace App\Models\CaseGoods;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Exception;
use Illuminate\Support\Facades\Log;

class CaseGoodsModel extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'name',
        'url',
        'price',
        'status',
        'goods_model_id'
    ];
    protected $table = "case_goods";
    protected $dateFormat = "U";
    protected $dates = ['deleted_at'];

    public static function getList($search_data = array(),$limit = 15,$page = 1){
        try {
            $status = $search_data['status'] ?? '';
            $keywords = $search_data['keywords'] ?? '';
            $goods_model_id = $search_data['goods_model_id'] ?? '';
            $sortName = $search_data['sort_name'] ?? 'case_goods.updated_at';
            $sortBy = $search_data['sort_by'] ?? 'DESC';
            $records = self::when($status,function ($query) use($status){
                    $query->where('status',$status);
                })
                ->when($keywords,function ($query) use($keywords){
                    $query->where('case_goods.name','like',"%$keywords%");
                })
                ->when($goods_model_id,function ($query) use($goods_model_id){
                    $query->where('case_goods.goods_model_id',$goods_model_id);
                })
                ->leftJoin('case_goods_model','case_goods_model.id','=','case_goods.goods_model_id')
                ->select(
                    'case_goods.id',
                    'case_goods.name',
                    'case_goods.url',
                    'case_goods.price',
                    'case_goods.status',
                    'case_goods.created_at',
                    'case_goods.updated_at',
                    'case_goods.goods_model_id',
                    'case_goods_model.name as goods_model_name'
                )
                ->orderBy($sortName,$sortBy)
                ->paginate($limit,['*'],'page',$page);
            return $records;
        }catch (\Exception $exception){
            Log::info($exception->getMessage());
            return false;
        }
    }
}
