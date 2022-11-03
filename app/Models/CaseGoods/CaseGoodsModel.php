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
        'title',
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
            $sortName = $search_data['sort_name'] ?? 'case_goods.updated_at';
            $sortBy = $search_data['sort_by'] ?? 'DESC';
            $records = self::when($status,function ($query) use($status){
                    $query->where('status',$status);
                })
                ->select(
                    'case_goods.id',
                    'case_goods.title',
                    'case_goods.url',
                    'case_goods.price',
                    'case_goods.status',
                    'case_goods.created_at',
                    'case_goods.updated_at',
                    'case_goods.goods_model_id'
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
