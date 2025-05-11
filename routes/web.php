<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Unusualify\Payable\Http\Controllers\PaymentController;
use Unusualify\Payable\Http\Controllers\TestController;

/*
|--------------------------------------------------------------------------
| AUTH Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::controller(PaymentController::class)
    ->as('payable.')
    ->prefix('payable')
    ->group(function(){

    Route::middleware(config('payable.middleware', []))->group(function(){
        Route::get('/pay/{payment_service_id}', 'pay')->name('pay');
        // Route::get('/cancel/{payment_service_id}/{payment_id}', 'cancel')->name('payable.cancel');
        // Route::get('/refund/{payment_service_id}/{payment_id}', 'refund')->name('payable.refund');
        Route::get('/cancel/{payment}', 'cancel')->name('cancel');
        Route::get('/refund/{payment}', 'refund')->name('refund');
    });

    Route::post('/return', 'response')->name('response');
    Route::get('/return', 'response')->name('response');
});


