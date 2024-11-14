<?php

namespace Unusualify\Payable\Services;

use Unusualify\Priceable\Facades\PriceService;
use Illuminate\Http\Request as HttpRequest;


class TebPosService extends PaymentService{

    protected $merchantID;
    protected $storeKey;
    protected $params = [];
    public $processType;
    public $rnd;

    public function __construct($mode = null)
    {

        parent::__construct();

        $this->setCredentials();

    }
    /*
    * Set proper .env variables to proper attributes
    */
    public function setCredentials()
    {
        $this->setConfig();
        $this->mode = $this->config['mode'];
        $tempConfig = $this->config[$this->mode];

        $this->url = $tempConfig['url'];
        $this->merchantID = $tempConfig['merchant_id'];
        $this->storeKey = $tempConfig['store_key'];

    }

    public function pay(array $params)
    {
        $endpoint = 'fim/est3Dgate';

        $this->rnd = microtime();
        $this->params += $params;
        $this->processType = 'Auth';

        $hash = $this->generateHash();
        // dd($this->params);
        $data = [
            'pan' => $this->params['card_no'],
            'Ecom_Payment_Card_ExpDate_Month' => $this->params['card_month'],
            'Ecom_Payment_Card_ExpDate_Year' => $this->params['card_year'],
            'cv2' => $this->params['card_cvv'],
            'amount' => $this->params['paid_price'],
            'cardType' => '',
            'clientid' => $this->merchantID,
            'oid' => $this->params['order_id'],
            'okUrl' => route('payable.response').'?payment_service=teb-pos',
            'failUrl' => route('payable.response').'?payment_service=teb-pos',
            'rnd' => $this->rnd,
            'hash' => $hash,
            'islemtipi' => $this->processType,
            // 'taksit' => $this->params['installment'],
            'taksit' => '',
            'currency' => $this->params['currency']->iso_4217_number,
            'storetype' => '3d_pay_hosting',
            'lang' => $this->params['locale'],
            'firmaadi' => '',
        ];

        // dd($data,$this->params['currency']);
        // $currency = PriceService::find($priceID)->currency;

        $this->createRecord(
        [
            'payment_gateway' => $this->serviceName,
            'order_id' => $this->params['order_id'],
            'payment_service_id' => $this->params['payment_service_id'],
            'price_id' => $this->params['price_id'],
            'currency_id' => $this->params['currency']->id,
            'email' => '', //Add email to data
            'installment' => $this->params['installment'],
            'amount' => $this->params['paid_price'],
            'parameters' => json_encode($data)
        ]);
        // dd($this->url,$endpoint);
        $response = $this->postReq($this->url,$endpoint,$data,[],'encoded');
        print($response);
        exit();


    }

    public function amountFormat($price)
    {
        return number_format((float)$price, 2, ',', '');
    }

    public function generateHash()
    {
        $map = [
        $this->merchantID,
        $this->params['order_id'],
        $this->params['paid_price'],
        route('payable.teb.return'). $this->returnQueries['success'],
        route('payable.teb.return') . $this->returnQueries['error'],
        $this->processType,
        $this->params['currency']->iso_4217_code,
        $this->rnd,
        $this->storeKey
        ];
        return base64_encode(pack('H*', sha1(implode('', $map))));
    }

    public function hydrateParams(array $params)
    {

    }

    public function getSchema()
    {

        $schema = [

            "cardname" => "_USER_cardname",
            "cardnumber" => "_USER_cardno",
            "cardexpiredatemonth" => "_USER_exp_month",
            "cardexpiredateyear" => "_USER_exp_year",
            "cardcvv2" => "_USER_cvv",
            "companyname" => "_SYSTEM_brand",
            "orderid" => "_SYSTEM_order_id",
            "customeremailaddress" => "_SYSTEM_email",
            "customeripaddress" => "_SYSTEM_ip",
            "txnamount" => "_SYSTEM_amount",
            "txncurrencycode" => "_SYSTEM_currency_no_4217",
            "txninstallmentcount" => "0",
            "lang" => "_SYSTEM_locale",
            "iscommission" => 0,
            'previous_url' => '_SYSTEM_previous_url',
            'email' => '_SYSTEM_email'
        ];

        return $schema;
    }

    public function handleResponse(HttpRequest $request){
        dd($request);

        if($request->MdStatus == 1 && $request->BankResponseCode == '00'){
            $params = [
                'status' => 'success',
                'id' => $request->query('payment_id'),
                'service_payment_id' => $request->paymentId,
                'order_id' => $request->conversationId,
                'order_data' => $request->conversationData
            ];

            $response = $this->updateRecord(
                $params['id'],
                'COMPLETED',
                $resp
            );
            $params['custom_fields']= $response['custom_fields'];
            dd($params);
        }else{

            $params = [
                'status' => 'fail',
                'payment_id' => $request->paymentId,
                'conversation_id' => $request->conversationId,
                'conversation_data' => $request->conversationData
            ];
            $response = $this->updateRecord(
                $params['id'],
                'COMPLETED',
                $resp
            );
        }
        return $this->generatePostForm($params, route(config('payable.return_url')));

        //     [
        //   "amount" => "10000"
        //   "clientid" => "400757361"
        //   "Ecom_Payment_Card_ExpDate_Month" => "06"
        //   "Ecom_Payment_Card_ExpDate_Year" => "2028"
        //   "ErrMsg" => "Wrong security code"
        //   "ErrorCode" => "3D-1004"
        //   "failUrl" => "https://business.b2press.com/payable/return?payment_service=teb-pos"
        //   "firmaadi" => null
        //   "islemtipi" => "Auth"
        //   "lang" => "en"
        //   "maskedCreditCard" => "4543 60** **** 9073"
        //   "MaskedPan" => "454360***9073"
        //   "oid" => "ORD-6703a77527c27"
        //   "okUrl" => "https://business.b2press.com/payable/return?payment_service=teb-pos"
        //   "ProcReturnCode" => "99"
        //   "Response" => "Declined"
        //   "storetype" => "3d_pay_hosting"
        //   "taksit" => "1"
        //   "traceId" => "6703a7750e3073523a579676ae097952"
        // ]
    }

}