<?php
namespace App\Http\Controllers;

use Unusualify\Payable\Payable;
use Illuminate\Http\Request;

class PaymentTestController extends Controller
{

    public function testGaranti()
    {
        $payable = new Payable('garanti-pos');
        $params = [
            'amount' => 1,
            'installment' => 1,
            'currency' => 'TRY',
            'locale' => 'tr',
            'order_id' => 'TEST-' . uniqid(),
            'card_name' => 'Ramazan Ayyildiz',
            'card_no' => '4912055018403926',
            'company_name' => 'B2Press',
            'card_month' => '03',
            'card_year' => '2029',
            'card_cvv' => '659',
            'user_email' => 'test@example.com',
            'user_ip' => request()->ip(),
        ];
        
        return $payable->pay($params);
    }

    public function testTebCommon()
    {
        $payable = new Payable('teb-common-pos');
        
        $params = [
            'paid_price' => 1,
            'installment' => 1,
            'currency' => 'TRY',
            'locale' => 'tr',
            'order_id' => 'TEST-' . uniqid(),
            'card_name' => 'Ramazan Ayyildiz',
            'card_no' => '4912055018403926',
            'company_name' => 'B2Press',
            'card_month' => '03',
            'card_year' => '2029',
            'card_cvv' => '659',
            'user_email' => 'test@example.com',
            'user_ip' => request()->ip(),
        ];
        
        return $payable->pay($params);
    }

    public function testPaypal()
    {
        $payable = new Payable('paypal');
        
        $params = [
            'paid_price' => 100.10,
            'installment' => 1,
            'currency' => 'USD',
            'order_id' => 'TEST-' . uniqid(),
            'user_email' => 'test@example.com',
            'user_name' => 'John',
            'user_surname' => 'Doe',
            'user_ip' => request()->ip(),
            'company_name' => 'B2Press',
            'locale' => 'tr-TR'
        ];
        
        return $payable->pay($params);
    }

    public function testPaypalRefund()
    {
        $payable = new Payable('paypal');
        
        $params = [
            /* 'capture_id' => '5JC53758C94870609', */
            'payment_id' =>17,
        ];
        
        $response = $payable->refund($params);
        dd($response);
    }

    public function testPaypalCancel()
    {
        $payable = new Payable('paypal');
        
        $params = [
            'authorization_id' => ' ',
            'payment_id' => 7,
        ];
        
        $response = $payable->cancel($params);
        dd($response);
    }

    public function testIdeal()
    {
        $payable = new Payable('ideal');
        
        $params = [
            'paid_price' => 100.10,
            'installment' => 1,
            'currency' => 'EUR',
            'issuer' => 'ABNANL2A',
            'order_id' => 'TEST-' . uniqid(),
            'user_email' => 'test@example.com',
            'user_ip' => request()->ip(),
        ];
        
        return $payable->pay($params);
    }

    public function testIdealQr()
    {
        $payable = new Payable('ideal-qr');
        
        $params = [
            'paid_price' => 100.10,
            'installment' => 1,
            'currency' => 'USD',
            'issuer' => 'ABNANL2A',
            'order_id' => 'TEST' . uniqid(),
            'user_email' => 'test@example.com',
            'description' => 'Test Payment',
            'user_ip' => request()->ip(),
        ];
        
        $qrimageurl= $payable->pay($params);
        return '<img src="'.$qrimageurl.'" />';
    }

    public function testIdealRefund()
    {
        $payable = new Payable('ideal');
        
        $params = [
            /* 'order_id' => 'TEST-6814ef7229c4b',
            'transaction_key' => '89819DD62261406A942D1FA029835852',
            'paid_price' => 100.10, */
            'payment_id' => 16,
        ];
        
        $response = $payable->refund($params);
        dd($response);
    }


    public function handleResponse(Request $request)
    {
        dd($request->all());
    }

    public function testRevolut()
    {
        $payable = new Payable('revolut');

        $params = [
            // Use either 'paid_price' (major units) or 'amount' (minor units)
            'amount' => 10.00,
            'currency' => 'EUR',
            'order_id' => 'ORDER-'.uniqid(),
            'user_email' => 'john@example.com',
            'user_id' => 1,
            'installment' => 1,
            'user_ip' => request()->ip(),
            'description' => 'Test Revolut payment',
        ];

        $result = $payable->pay($params);
       
        if (($result['type'] ?? null) === 'widget') {
            return view('checkout.revolut', [
                'token' => $result['token'] ?? '',
                'env' => $result['env'] ?? 'sandbox',
            ]);
        }

        abort(400, $result['message'] ?? 'Unable to initialize Revolut order');
    }
}