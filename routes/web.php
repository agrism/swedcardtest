<?php

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


Route::get('payment', 'PaymentController@create');
Route::get('handle', 'PaymentController@handle');

Route::get('expire', function (){
    echo 'expire';
    \Illuminate\Support\Facades\Log::info('-expire');
});


Route::get('return', function (){
    echo 'return';
    \Illuminate\Support\Facades\Log::info('-return');
    (new \App\Http\Controllers\PaymentController())->confirm();
});

Route::get('error', function (){
    echo 'error';
    \Illuminate\Support\Facades\Log::info('-error');
});