<?php

namespace Unusualify\Payable\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\SystemPayment\Entities\PaymentService;
use Unusualify\Payable\Payable;
use Unusualify\Payable\Facades\Payable as PayableFacade;
use Unusualify\Payable\Models\Payment;

class PaymentController extends Controller
{

    public function cancel(Request $request, Payment $payment)
    {
        $cancelResponse = PayableFacade::setService($payment->payment_gateway)
            ->cancel($payment->id, $payment->response);

        if($request->ajax()){
            return response()->json([
                'message' => $cancelResponse['message'] ?? 'Cancelled successfully',
                'variant' => $cancelResponse['status'] ?? 'success',
            ]);
        }

        return redirect()->back()->with([
            'message' => $cancelResponse['message'] ?? 'Cancelled successfully',
            'variant' => $cancelResponse['status'] ?? 'success',
        ]);

        // $status = PayableFacade::cancel([
        //     'ip' => '85.34.78.112',
        //     'payment_id' => $payment_id,
        //     'conversation_id' => $conversation_id,
        //     'reason' => 'My reason',
        //     'price' => '1.2',
        //     'locale' => 'tr',
        // ]);
        // dd($status);
    }

    public function refund(Request $request, Payment $payment)
    {
        $refundResponse = PayableFacade::setService($payment->payment_gateway)
            ->refund($payment->id, $payment->response);

        if($request->ajax()){
            return response()->json([
                'message' => $refundResponse['message'] ?? 'Refunded successfully',
                'variant' => $refundResponse['status'] ?? 'success',
            ]);
        }

        return redirect()->back()->with([
            'message' => $refundResponse['message'] ?? 'Refunded successfully',
            'variant' => $refundResponse['status'] ?? 'success',
        ]);

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

    public function pay($params)
    {
        //Params should include $payment_service_id, $price_id
        //This controller functions should be moved to SystemPayment module instead since it has $payment_service_id
        $paymentServiceName = PaymentService::find($params['payment_service_id'])->name;
        $payment = new Payable($paymentServiceName);
        $payment->pay($params);
        dd($payment);
    }

    public function response(Request $request)
    {
        $paymentServiceName = $request->get('payment_service');

        $payable = new Payable($paymentServiceName);

        return $payable->handleResponse($request);
    }
}
