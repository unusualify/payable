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

// Route::controller(TestController::class)->prefix('test-api')->group(function(){
//   Route::get('/',  'test')->name('payable.test');

//   Route::get('/paypal-return', 'paypalResponse')->name('payable.paypal.return');
//   Route::get('/paypal', 'testPaypal')->name('payable.paypal.pay');

//   Route::post('/garanti-return', 'garantiResponse')->name('payable.garanti.return');

//   Route::post('/teb-return', 'tebResponse')->name('payable.teb.return');

//   Route::post('/teb-common-return', 'tebCommonResponse')->name('payable.teb-common.return');

//   Route::get('/iyzico', 'testIyzico')->name('payable.iyzico.pay');
//   Route::post('/iyzico-return', 'iyzicoResponse')->name('payable.iyzico.return');

//   Route::get('/cancel/{payment_service_id}/{payment_id}/{conversation_id}', 'cancel')->name('test.payable.cancel');

//   Route::get('/refund/{payment_service_id}/{payment_id}/{conversation_id}', 'refund')->name('test.payable.refund');

//   Route::get('/show/{payment_service_id}/{order_id}', 'show')->name('test.payable.show');

//   Route::get('/test-relation', 'testRelation')->name('test.payable.relation');


// });


Route::controller(PaymentController::class)->prefix('payable')->group(function(){

    Route::get('/pay/{payment_service_id}', 'pay')->name('payable.pay');
    Route::get('/cancel/{payment_service_id}/{payment_id}', 'cancel')->name('payable.cancel');
    Route::get('/refund/{payment_service_id}/{payment_id}', 'refund')->name('payable.refund');

    Route::group([],function () {
        Route::post('/return', 'response')->name('payable.response');
        Route::get('/return', 'response')->name('payable.response');
    });

});


