<?php
namespace App\Http\Controllers;

use Unusualify\Payable\Payable;
use Illuminate\Http\Request;

class RamazanTestController extends Controller
{

    public function testGaranti()
    {
        $payable = new Payable('garanti-pos');
        
        $params = [
            'paid_price' => 1000,
            'installment' => 1,
            'currency' => 'TRY',
            'locale' => 'tr',
            'order_id' => 'TEST-' . uniqid(),
            'card_name' => 'John Doe',
            'company_name' => 'B2Press',
            'card_no' => '9792290735587016',
            'card_month' => '02',
            'card_year' => '2026',
            'card_cvv' => '790',
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
            'currency' => 'USD',
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
            'description' => 'Test purchase',
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
}