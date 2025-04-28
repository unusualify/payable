<?php

namespace Unusualify\Payable\Services;

use Unusualify\Payable\Services\PaymentService;
use Unusualify\Payable\Services\Currency;
use Illuminate\Http\Request as HttpRequest;


class GarantiPosService extends PaymentService{

    public $version = "v0.01";

    protected $terminalID ; //Terminal id

    protected $merchantID;

    protected $provUserID; // Terminal prov user name

    protected $terminalUserID; // Terminal user id

    protected $provUserPassword; // Terminal prov user password

    protected $garantiPayProvUserID; // GarantiPay için prov username

    protected $garantiPayProvUserPassword; // GarantiPay için prov user password

    protected $paymentType;  // Payment type - for credit cards: "creditcard", for GarantiPay : "garantipay"

    protected $storeKey;

    public $timeOutPeriod = "180";

    public $paymentRefreshTime = "1"; // Amount of time that user will be waiting after payment

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
        
        $this->url = $tempConfig['url'];
        $this->merchantID = $tempConfig['merchant_id'];
        $this->terminalID = $tempConfig['terminal_id'];
        $this->terminalUserID = $tempConfig['terminal_userid'];
        $this->provUserID = $tempConfig['provision_userid'];
        $this->provUserPassword = $tempConfig['provision_pw'];
        $this->garantiPayProvUserID = $tempConfig['pay_provision_userid'];
        $this->garantiPayProvUserPassword = $tempConfig['pay_provision_pw'];
        $this->storeKey = $tempConfig['secure_key'];
        $this->paymentType = $this->config['payment_type'];

        $this->params = [
            "companyname" => $tempConfig['company_name'],
            "refreshtime" => $this->paymentRefreshTime,
            "secure3dsecuritylevel" => "3D_PAY",
            "txntype" => "sales",
            "apiversion" => $tempConfig['api_version'],
            "mode" => $this->mode,
            "terminalprovuserid" => $this->provUserID,
            "terminaluserid" => $this->terminalUserID,
            "terminalid" => $this->terminalID,
            "terminalmerchantid" => $this->merchantID,
        ];
    }

    public function pay(array $params)
    {
        $endpoint = 'servlet/gt3dengine';
        $this->params['txntimestamp'] = time();
        $this->params += $params;
        $payment = $this->createRecord([
            'serviceName' => $this->serviceName,
            'order_id' => $this->params['order_id'],
            'currency' => $this->params['currency'],
            'amount' => $this->params['paid_price'],
            'email' => $this->params['user_email'],
            'installment' => $this->params['installment'],
            'parameters' => json_encode($this->params)
        ]);

        $this->params['successurl'] = route('payable.response').'?payment_service=garanti-pos'.'&payment_id='.$payment->id;
        $this->params['errorurl'] = route('payable.response').'?payment_service=garanti-pos'.'&payment_id='.$payment->id;


        $this->params['secure3dhash'] = $this->GenerateHashData($payment->id);
        $this->headers['Content-Type'] ='application/x-www-form-urlencoded';

        $resp = $this->postReq(
        $this->url,
        $endpoint,
        $this->hydrateParams($this->params),
        $this->headers,
            'encoded',
        );

        return print_r($resp);
    }


    private function GenerateSecurityData()
    {
        $password = $this->provUserPassword;
        $data = [
            $password,
            str_pad((int)$this->terminalID, 9, 0, STR_PAD_LEFT)
        ];
        $shaData =  sha1(implode('', $data));
        return strtoupper($shaData);
    }

    public function GenerateHashData($payment_id)
    {
        $orderId  = $this->params['order_id']; 
        $terminalId =  $this->terminalID;
        $amount = $this->formatPrice($this->params['paid_price']); 
        $currencyCode = Currency::getNumericCode($this->params['currency']);
        $storeKey = $this->storeKey;
        $installmentCount = "";
        $successUrl = route('payable.response').'?payment_service=garanti-pos'.'&payment_id='.$payment_id;
        $errorUrl = route('payable.response').'?payment_service=garanti-pos'.'&payment_id='.$payment_id;
        $type = $this->params['txntype'];
        $hashedPassword = $this->GenerateSecurityData();      
        return strtoupper(hash('sha512', $terminalId . $orderId . $amount . $currencyCode . $successUrl . $errorUrl . $type . $installmentCount . $storeKey . $hashedPassword));
    }

    public function hydrateParams(array $params)
    {
        if($params['mode'] == 'live')
            $params['mode'] = 'PROD';
        else
            $params['mode'] = 'TEST';

        $params['orderid'] = $params['order_id'];
        $params['txnamount'] = $this->formatPrice($params['paid_price']);
        $params['lang'] = $params['locale'];
        $params['txncurrencycode'] = Currency::getNumericCode($params['currency']);
        $params['customeremailaddress'] = $params['user_email'];
        $params['customeripaddress'] = $params['user_ip'];
        $params['txntimestamp'] = date('Y-m-d\TH:i:s\Z', time());
        $params['txninstallmentcount'] = "";
        $params['cardholdername'] = $params['card_name'];
        $params['cardnumber'] = $params['card_no'];
        $params['cardexpiredatemonth'] = $params['card_month'];
        $params['cardexpiredateyear'] = $this->formatCardYear($params['card_year']);
        $params['cardcvv2'] = $params['card_cvv'];
        return $params;
    }

    public function handleResponse(HttpRequest $request){
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

        $resp = array_filter($request->all(), function($key) use ($paramsToRemoved) {
            return !in_array($key, $paramsToRemoved);
        }, ARRAY_FILTER_USE_KEY);

        if($request->mdstatus == 1){
            $params = [
                'status' => 'success',
                'id' => $request->query('payment_id'),
                'payment_service' => $request->payment_service,
                'order_id' => $request->order_id,
                'order_data' => $request->all()
            ];

            $response = $this->updateRecord(
                $params['id'],
                'COMPLETED',
                $resp
            );

        }else{
            $params = [
                'status' => 'fail',
                'id' => $request->query('payment_id'),
                'payment_service' => $request->payment_service,
                'order_id' => $request->order_id,
                'order_data' => $request->all()
            ];

            $response = $this->updateRecord(
                $params['id'],
                'FAILED',
                $resp
            );


        }
        return $this->generatePostForm($params, route(config('payable.return_url')));
    }

    public function formatPrice($price){
        return round($price, 2) * 100;
    }

    public function formatCardYear($year){
        return substr($year, 2);
    }
}
