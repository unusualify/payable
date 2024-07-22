<?php

namespace Unusualify\Payable\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\SystemPayment\Entities\PaymentService;
use Unusualify\Payable\Facades\Payment;
use Unusualify\Payable\Payable;
use Unusualify\Payable\Services\GarantiPosService;
use Unusualify\Payable\Services\TebCommonPosService;
use Unusualify\Payable\Services\TebPosService;

// use Srmklive\PayPal\PayPalFacadeAccessor as PayPalClient;


class PaymentController extends Controller
{

  public function paypalResponse(Request $request)
  {
    $allParams = $request->query();

    if ($allParams['success'] == true) {
      $paypal = new Payable('paypal');
      $resp = $paypal->service->capturePayment($allParams['token']);
      // dd($resp);
      $paypal->service->updateRecord(
        $allParams['token'],
        'COMPLETED',
        json_encode($resp)
      );
    }
    // dd('here');
  }

  public function garantiResponse(Request $request)
  {
    dd($request);
  }

  public function tebResponse(Request $request)
  {
    dd($request);
  }

  public function tebCommonResponse(Request $request)
  {
    if ($request->BankResponseCode == "00") {

      //Update payment model with the response field and remove parameters
      TebCommonPosService::updateRecord($request->OrderId, 'COMPLETED', $request->all());
      // dd('success');
    } else {
      TebCommonPosService::updateRecord($request->OrderId, 'CANCELED', $request->all());
    }
    // dd($request);
  }

  public function iyzicoResponse(Request $request)
  {
    $payment = new Payable('iyzico');
    if ($request->status == 'success') {
      $params = [
        'payment_id' => $request->paymentId,
        'conversation_id' => $request->conversationId,
        'conversation_data' => $request->conversationData
      ];
      // dd('here');
      $payment->service->completePayment($params);
      // dd($params);
      // dd('finished');
      // dd($payment->service->updateRecord($request->conversationId, 'COMPLETED',$request->all()), $request->all());
    }
    // dd($request);
  }
  public function cancel(Request $request, $slug, $payment_id, $conversation_id)
  {
    $payment = new Payable($slug);
    $status = $payment->service->cancel([
      'ip' => '85.34.78.112',
      'payment_id' => $payment_id,
      'conversation_id' => $conversation_id,
      'reason' => 'My reason',
      'price' => '1.2',
      'locale' => 'tr',
    ]);
    // dd($status);
  }

  public function refund($slug, $orderId, $conversation_id)
  {
    $payment = new Payable($slug);
    if ($slug == 'iyzico') {
      $status = $payment->service->refund([
        'ip' => '85.34.78.112',
        'payment_id' => $orderId,
        'conversation_id' => $conversation_id,
        'price' => '1.2',
        'locale' => 'tr',
      ]);
    } else if ($slug == 'paypal') {
      $payment->service->getAccessToken();
      $captureId = json_decode(Payment::where('order_id', $orderId)->get()[0]->response)
        ->purchase_units[0]
        ->payments
        ->captures[0]
        ->id;
      // dd($captureId);
      $status = $payment->service->refund([
        'capture_id' => $captureId,
        'order_id' => $orderId,
        'amount' => '100.00',
        'priceID' => 1
      ]);
    }
    dd($status);
    
  }
  public function show(Request $request, $slug, $orderId)
  {
    $payable = new Payable($slug);
    
    return $payable->service->showFromSource($orderId);
    // dd(json_encode($resp));
  }
  public function pay($params){

    //Params should include $payment_service_id, $price_id
    //This controller functions should be moved to SystemPayment module instead since it has $payment_service_id
    $paymentServiceName = PaymentService::find($params['payment_service_id'])->name;
    $payment = new Payable($paymentServiceName);
    $payment->pay($params, $params['price_id']);
    dd($payment);
  }
}
