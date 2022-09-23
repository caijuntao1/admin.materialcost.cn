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

Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
Route::any('h2ddd/exChangeSteps', [App\Http\Controllers\h2ddd\UserController::class, 'exChangeSteps2']);
Route::any('wechat', [App\Http\Controllers\Wechat\WechatReplyController::class, 'reply']);
