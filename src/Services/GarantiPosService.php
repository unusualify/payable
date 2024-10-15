<?php

namespace Unusualify\Payable\Services;

use Unusualify\Payable\Services\PaymentService;
use Illuminate\Http\Request as HttpRequest;


class GarantiPosService extends PaymentService{

    public $version = "v0.01";

    protected $terminalID ; //Terminal id

    protected $merchantID;

    protected $provUserID; // Terminal prov user name

    protected $provUserPassword; // Terminal prov user password

    protected $garantiPayProvUserID; // GarantiPay için prov username

    protected $garantiPayProvUserPassword; // GarantiPay için prov user password

    protected $paymentType;  // Payment type - for credit cards: "creditcard", for GarantiPay : "garantipay"

    protected $storeKey;

    public $timeOutPeriod = "180";

    public $paymentRefreshTime = "180"; // Amount of time that user will be waiting after payment

    public $addCampaignInstallment = "N";

    public $totalInstallamentCount = "0";

    public $installmentOnlyForCommercialCard = "N";

    public $params = [];

    public $debugPaymentUrl = "https://eticaret.garanti.com.tr/destek/postback.aspx";



    // GarantiPay tanımlamalar
    public $garantiPay = "Y"; // Usage of GarantiPay: Y/N
    public $bnsUseFlag = "Y"; // Usage of Bonu : Y/N
    public $fbbUseFlag = "Y"; // Usage of Fbb: Y/N
    public $chequeUseflag = "N"; // Usage of cheque: Y/N
    public $mileUseflag = "N"; // Usage of Mile: Y/N



    //Translate the status messages
    public $mdStatuses = array(
        0 => "Doğrulama başarısız, 3-D Secure imzası geçersiz",
        1 => "Tam doğrulama",
        2 => "Kart sahibi banka veya kart 3D-Secure üyesi değil",
        3 => "Kartın bankası 3D-Secure üyesi değil",
        4 => "Kart sahibi banka sisteme daha sonra kayıt olmayı seçmiş",
        5 => "Doğrulama yapılamıyor",
        7 => "Sistem hatası",
        8 => "Bilinmeyen kart numarası",
        9 => "Üye işyeri 3D-Secure üyesi değil",
    );


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
        $tempConfig = $this->config[$this->mode];
        // dd($this->mode, $this->config);
        // dd($tempConfig);
        $this->url = $tempConfig['url'];
        $this->merchantID = $tempConfig['merchant_id'];
        $this->terminalID = $tempConfig['terminal_id'];
        $this->provUserID = $tempConfig['provision_userid'];
        $this->provUserPassword = $tempConfig['provision_pw'];
        $this->garantiPayProvUserID = $tempConfig['pay_provision_userid'];
        $this->garantiPayProvUserPassword = $tempConfig['pay_provision_pw'];
        $this->storeKey = $tempConfig['secure_key'];
        $this->paymentType = $this->config['payment_type'];
        // dd($this->mode);
        $this->params = [
            "refreshtime" => $this->paymentRefreshTime,
            "paymenttype" => $this->paymentType,
            "secure3dsecuritylevel" => "3D_PAY",
            "txntype" => "sales",

            "apiversion" => $tempConfig['api_version'],
            "mode" => $this->mode,
            "terminalprovuserid" => $this->provUserID,
            "terminaluserid" => $this->provUserID,
            "terminalid" => $this->terminalID,
            "terminalmerchantid" => $this->merchantID,

            "successurl" => '',
            "errorurl" => '',

            "txntimeoutperiod" => $this->timeOutPeriod,
            "addcampaigninstallment" => $this->addCampaignInstallment,
            "totallinstallmentcount" => $this->totalInstallamentCount,
            "installmentonlyforcommercialcard" =>$this->installmentOnlyForCommercialCard,
        ];
        // dd($this);
    }

    public function pay(array $params)
    {
        $endpoint = 'servlet/gt3dengine';
        $this->params['txntimestamp'] = time();
        $this->params += $params;
        // dd($this->params);
        $payment = $this->createRecord([
            'serviceName' => $this->serviceName,
            'order_id' => $this->params['order_id'],
            'currency_id' => $this->params['currency']->id,
            'amount' => $this->params['paid_price'],
            'email' => '', //Add email to data
            'price_id' => $this->params['price_id'],
            'payment_service_id' => $this->params['payment_service_id'],
            'installment' => $this->params['installment'],
            'parameters' => json_encode($this->params)
        ]);
        
        $this->params['successurl'] = route('payable.response').'?payment_service=garanti-pos'.'&payment_id='.$payment->id;
        $this->params['errorurl'] = route('payable.response').'?payment_service=garanti-pos'.'&payment_id='.$payment->id;


        $this->params['secure3dhash'] = $this->generateHash($payment->id);
        $this->headers['Content-Type'] ='application/x-www-form-urlencoded';

        // dd($this->hydrateParams($this->params));

        $resp = $this->postReq(
        $this->url,
        $endpoint,
        $this->hydrateParams($this->params),
        $this->headers,
            'encoded',
        );

        // dd($this->params);

        return print_r($resp);
    }

    public function generateHash($payment_id)
    {
        // dd($this->params);
        $map = [
            $this->terminalID,
            $this->params['order_id'],
            $this->params['paid_price'],
            $this->params['currency']->iso_4217_number,
            route('payable.response').'?payment_service=garanti-pos'.'&payment_id='.$payment_id,
            route('payable.response').'?payment_service=garanti-pos'.'&payment_id='.$payment_id,
            $this->params['txntype'],
            "0",
            $this->storeKey,
            $this->generateSecurityData()
        ];
        // dd($map);
        // $terminalId . $orderId . $amount . $currencyCode . $successUrl . $errorUrl . $type . $installmentCount . $storeKey . $hashedPassword
        // dd(implode('', $map),strtoupper(hash('sha512', implode('', $map))), $this->generateSecurityData());
        return strtoupper(hash('sha512', implode('', $map)));
    }

    public function generateSecurityData()
    {
        // dd($this->provUserPassword . $this->terminalID);
        // BACK UP SECURITY DATA FROM payment.b2press.com A376141CF086D02295CAE8179081A0F995392CBD
        return strtoupper(sha1($this->provUserPassword . '0' .$this->terminalID));
    }

    public function hydrateParams(array $params)
    {
        // $requiredParams = [
        //     'mode',
        //     'apiversion',
        //     'secure3dsecuritylevel',
        //     'terminalprovuserid',
        //     'terminaluserid',
        //     'terminalmerchantid',
        //     'terminalid',
        //     'orderid',
        //     'successurl',
        //     'errorurl',
        //     'customeremailaddress',
        //     'customeripaddress',
        //     'companyname',
        //     'lang',
        //     'txntimestamp',
        //     'refreshtime',
        //     'secure3dhash',
        //     'txnamount',
        //     'txncurrencycode',
        //     'txninstallmentcount',
        //     'cardholdername',
        //     'cardnumber',
        //     'cardexpiredatemonth',
        //     'cardexpiredateyear',
        //     'cardcvv2',
        //     'txntype',
        //     'addcampaigninstallment',
        //     'totallinstallmentcount',
        //     'installmentonlyforcommercialcard'
        // ];

        if($params['mode'] == 'live')
            $params['mode'] = 'PROD';
        else
            $params['mode'] = 'TEST';

        $params['orderid'] = $params['order_id'];
        $params['txnamount'] = $params['paid_price'];
        $params['lang'] = $params['locale'];
        $params['txncurrencycode'] = $params['currency']->iso_4217_number;
        $params['customeremailaddress'] = $params['user_email'];
        $params['customeripaddress'] = $params['user_ip'];
        $params['companyname'] = 'B2Press';
        $params['txntimestamp'] = time();
        $params['txninstallmentcount'] = "0";
        $params['cardholdername'] = $params['card_name'];
        $params['cardnumber'] = $params['card_no'];
        $params['cardexpiredatemonth'] = $params['card_month'];
        $params['cardexpiredateyear'] = $this->formatCardYear($params['card_year']);
        $params['cardcvv2'] = $params['card_cvv'];
        
        // Remove all keys from $params that are not in $requiredParams
        // $filteredParams = array_intersect_key($params, array_flip($requiredParams));
        // dd($params, $filteredParams);
        return $params;
        // return $filteredParams;
    }

    public function handleResponse(HttpRequest $request){
        //TODO: retrieve paid item id from request
        $paramsToRemoved = [
            'card_name',
            'card_no',
            'card_year',
            'card_month',
            'card_cvv',
            'user_ip',
            'oid',
            'orderid',
            'terminaluserid',
            'txnamount',
            'terminalid',
        ];
        // dd($request->all());
        $resp = array_filter($request->all(), function($key) use ($paramsToRemoved) {
            return !in_array($key, $paramsToRemoved);
        }, ARRAY_FILTER_USE_KEY);
        if($request->mdstatus == 1){
            $params = [
                'status' => 'success',
                'id' => $request->query('payment_id'),
                'service_payment_id' => $request->paymentId,
                'order_id' => $request->order_id,
                'order_data' => $request->all()
            ];
            
            $response = $this->updateRecord(
                $params['id'],
                'COMPLETED',
                $resp
            );
            $params['custom_fields']= $resp['custom_fields'];
            
        }else{
            $params = [
                'status' => 'fail',
                'payment_id' => $request->paymentId,
                'conversation_id' => $request->conversationId,
                'conversation_data' => $request->conversationData
            ];
            $response = $this->updateRecord(
                $params['id'],
                'FAILED',
                $resp
            );
        }
        return $this->generatePostForm($params, route(config('payable.return_url')));
    }

    public function formatCardYear($year){
        return substr($year, 2);
    }
}
