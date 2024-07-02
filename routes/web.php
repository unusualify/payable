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
Route::controller(TestController::class)->prefix('test-api')->group(function(){
  Route::get('/',  'test')->name('payable.test');

  Route::get('/paypal-return', 'paypalResponse')->name('payable.paypal.return');
  Route::post('/garanti-return', 'garantiResponse')->name('payable.garanti.return');

  Route::post('/teb-return', 'tebResponse')->name('payable.teb.return');

  Route::post('/teb-common-return', 'tebCommonResponse')->name('payable.teb-common.return');

  Route::get('/iyzico', 'testIyzico')->name('payable.iyzico.pay');


  Route::post('/iyzico-return', 'iyzicoResponse')->name('payable.iyzico.return');
  
  Route::get('/cancel/{slug}/{payment_id}/{conversation_id}', 'cancel')->name('test.payable.cancel');
  
  Route::get('/refund/{slug}/{payment_id}/{conversation_id}', 'refund')->name('test.payable.refund');


});

Route::controller(PaymentController::class)->prefix('payable')->group(function(){
  
  Route::get('/pay/{slug}', 'pay')->name('payable.pay');
  Route::get('/cancel/{slug}/{payment_id}', 'cancel')->name('payable.cancel');
  Route::get('/refund/{slug}/{payment_id}', 'refund')->name('payable.refund');
  
});


