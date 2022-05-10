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
    return view('auth/login');
});

Route::get('/getFriends', 'App\Http\Controllers\HomeController@getFriends');
Route::post('/session/create', 'App\Http\Controllers\SessionController@create');
Route::post('/session/{session}/send', 'App\Http\Controllers\ChatController@send');
Route::get('/session/{session}/chats', 'App\Http\Controllers\ChatController@chats');
Route::post('/session/{session}/read', 'App\Http\Controllers\ChatController@read');
Route::post('/session/{session}/clear', 'App\Http\Controllers\ChatController@clear');
Route::post('/session/{session}/block', 'App\Http\Controllers\BlockController@block');
Route::post('/session/{session}/unblock', 'App\Http\Controllers\BlockController@unblock');

Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
