<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
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
// dd(
//     unusualConfig('enabled.users-management')
// );
// Auth::routes();
// dd('here');

Route::controller(TestController::class)->prefix('test-api')->group(function(){
  Route::get('/',  'test')->name('payable.test');

  Route::get('/paypal-return', 'paypalResponse')->name('payable.paypal.return');
  Route::post('/garanti-return', 'garantiResponse')->name('payable.garanti.return');

  Route::post('/teb-return', 'tebResponse')->name('payable.teb.return');


});

