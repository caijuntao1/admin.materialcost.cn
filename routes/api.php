<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FirstPi\FirstPiController;
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
//Route::get('FirstPi/getFirstPiAllData',[FirstPiController::class , 'getFirstPiAPIData']);
Route::get('FirstPi/updateAllData',[FirstPiController::class , 'updateAllData']);
Route::get('FirstPi/getList',[FirstPiController::class , 'getList']);
Route::get('FirstPi/getCategoryList',[FirstPiController::class , 'getCategoryList']);
