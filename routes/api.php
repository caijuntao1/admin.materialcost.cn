<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FirstPi\FirstPiController;
use App\Http\Controllers\h2ddd\UserController;
use App\Http\Controllers\h2ddd\AutoExChange;
use App\Http\Controllers\Administrator\UsersController;
use App\Http\Controllers\CaseGoods\GoodsController;
use App\Http\Controllers\AliOss\ServiceController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
//登录注册
Route::group(['namespace' => 'Administrator'],function (){
    Route::any('users/login',[UsersController::class,'login']);
});

Route::group(['middleware'=>'refresh.token'],function (){
    //测试是否携带token
    Route::any('users/test',[UsersController::class,'test']);
    Route::post('/Case/saveGoodsDetail',[GoodsController::class,'saveGoodsDetail']);
});
//电商
Route::get('/Case/getGoodsList',[GoodsController::class,'getGoodsList']);
Route::post('/Case/uploadImage',[GoodsController::class,'uploadImage']);


Route::get('FirstPi/getFirstPiAllData',[FirstPiController::class , 'getFirstPiAPIData']);
Route::get('FirstPi/updateAllData',[FirstPiController::class , 'updateAllData']);
Route::get('FirstPi/getList',[FirstPiController::class , 'getList']);
Route::get('FirstPi/getCategoryList',[FirstPiController::class , 'getCategoryList']);

//氢动八蛇
Route::get('h2ddd/exChangeSteps',[UserController::class , 'exChangeSteps']);
Route::get('h2ddd/getProxyIp2',[UserController::class , 'getProxyIp2']);
Route::get('h2ddd/autoExChange',[AutoExChange::class , 'autoExChange']);

//阿里云OSS
Route::get('/AliOss/getOssProjectList',[ServiceController::class , 'getOssProjectList']);
