<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});
Route::get('/W9UItT62gs.txt', function () {
    return view('welcome');
});
Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
Route::any('h2ddd/exChangeSteps', [App\Http\Controllers\h2ddd\UserController::class, 'exChangeSteps2']);
Route::any('h2ddd/testLogin', [App\Http\Controllers\h2ddd\UserController::class, 'testLogin']);
Route::any('h2ddd/getChangeQty', [App\Http\Controllers\h2ddd\UserController::class, 'getChangeQty']);
Route::any('h2ddd/rechargeChangeQty', [App\Http\Controllers\h2ddd\UserController::class, 'rechargeChangeQty']);
Route::any('wechat', [App\Http\Controllers\Wechat\WechatReplyController::class, 'reply']);
Route::any('pingHKOKApi', [App\Http\Controllers\testApi\testApiController::class, 'pingHKOKApi']);
Route::get('xmeta/updateGoods', [App\Http\Controllers\XMeta\ArchiveGoodsController::class, 'updateGoods']);
Route::get('xmeta/getDetail', [App\Http\Controllers\XMeta\ArchiveGoodsController::class, 'getDetail']);
Route::get('xmeta/getMinPriceList', [App\Http\Controllers\XMeta\ArchiveGoodsController::class, 'getMinPriceList']);

Route::get('XL/updateGoods', [App\Http\Controllers\XMeta\XLArchiveGoodsController::class, 'updateGoods']);
Route::get('XL/getDetail', [App\Http\Controllers\XMeta\XLArchiveGoodsController::class, 'getDetail']);
Route::get('XL/getMinPriceList', [App\Http\Controllers\XMeta\XLArchiveGoodsController::class, 'getMinPriceList']);
