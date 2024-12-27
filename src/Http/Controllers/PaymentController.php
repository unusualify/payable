<?php

namespace Unusualify\Payable\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\SystemPayment\Entities\PaymentService;
use Unusualify\Payable\Facades\Payment;
use Unusualify\Payable\Payable;

class PaymentController extends Controller
{

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
    $payment->pay($params);
    dd($payment);
  }

  public function response(Request $request){
    // dd(auth()->user());
    // return redirect()->route('admin.system.system_payment.test-gunes');
    // $test = config('payable.session_key').'_payment_service';
    // dd(session()->all());

    // dd($paymentService, $test);
    // dd(Session::get(config('payable.session_key'). '.payment_service'));
    // $payment = new Payable(Session::get(config('payable.session_key'). 'payment_service'));
    // dd($request->only(['payment_service'])['payment_service']);
    $payment = new Payable($request->only(['payment_service'])['payment_service']);
    // dd($request->only(['payment_service'])['payment_service'], $payment, $payment->handleResponse($request));
    // dd($payment);
    return $payment->handleResponse($request);
    // dd($response);
  }
}
