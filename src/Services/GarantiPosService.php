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
    $this->url = $tempConfig['url'];
    $this->merchantID = $tempConfig['merchant_id'];
    $this->terminalID = $tempConfig['terminal_id'];
    $this->provUserID = $tempConfig['provision_userid'];
    $this->provUserPassword = $tempConfig['provision_pw'];
    $this->garantiPayProvUserID = $tempConfig['pay_provision_userid'];
    $this->garantiPayProvUserPassword = $tempConfig['pay_provision_pw'];
    $this->storeKey = $this->config['store_key'];
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

        "successurl" => route('payable.garanti.return') . '?action=success',
        "errorurl" => route('payable.garanti.return') . '?action=error',

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
    $this->params['secure3dhash'] = $this->generateHash();
    $this->headers['Content-Type'] ='application/x-www-form-urlencoded';

    $resp = $this->postReq(
      $this->url,
      $endpoint,
      $this->params,
      $this->headers,
      'encoded',
    );

	// dd($this->params);
    $this->createRecord([
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
    return print_r($resp);
  }

  public function generateHash()
  {
	// dd($this->params);
    $map = [
      $this->terminalID,
      $this->params['order_id'],
      $this->params['paid_price'],
      $this->params['currency']->iso_4217_number,
      route('payable.response').'?payment_service=garanti-pos',
      route('payable.response').'?payment_service=garanti-pos',
      $this->params['txntype'],
      $this->params['installment'],
      $this->storeKey,
      $this->generateSecurityData()
    ];
    return strtoupper(hash('sha512', implode('', $map)));
  }

  public function generateSecurityData()
  {
    return strtoupper(sha1($this->provUserPassword . $this->terminalID));
  }

  public function hydrateParams(array $params)
  {

  }

  public function getSchema(){

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
        // [
        //     "mdstatus" => "7"
        //     "mderrormessage" => "Guvenlik Kodu hatali"
        //     "errmsg" => "Guvenlik Kodu hatali"
        //     "clientid" => "30691297"
        //     "oid" => "91A2E85FA4084FBB8A53C8EDF9ACEF24"
        //     "response" => "Error"
        //     "procreturncode" => "99"
        //     "user_country" => "Default Country"
        //     "currency" => array:6 [▶]
        //     "user_address" => "123 Main St"
        //     "card_no" => "4543604278609073"
        //     "installment" => "1"
        //     "terminalmerchantid" => "7000679"
        //     "txntype" => "sales"
        //     "refreshtime" => "180"
        //     "card_month" => "06"
        //     "user_registration_date" => "2024-10-05 11:24:09"
        //     "mode" => "sandbox"
        //     "card_cvv" => "123"
        //     "user_name" => "Administrator"
        //     "price_id" => "19"
        //     "user_city" => "Default City"
        //     "user_ip" => "127.0.0.1"
        //     "custom_fields" => array:1 [▶]
        //     "terminalid" => "30691297"
        //     "order_id" => "ORD-6703bd3183647"
        //     "secure3dhash" => "16182A74E2627732FB63487658BD733977D84FCEBB2458D0D4032BCBC6F99BFF02C1487A5F6EB2FAA8DDF7B6D7093163CAFF7A6E9AE627EA75BA1D581F20A6B3"
        //     "paymenttype" => "creditcard"
        //     "terminalprovuserid" => "PROVAUT"
        //     "items" => array:1 [▶]
        //     "locale" => "en"
        //     "user_id" => "1"
        //     "basket_id" => "6703bd31838e2"
        //     "errorurl" => "https://business.b2press.com/test-api/garanti-return?action=error"
        //     "payment_service_id" => "3"
        //     "payment_group" => "PRODUCT"
        //     "price" => "8333"
        //     "totallinstallmentcount" => "0"
        //     "paid_price" => "10000"
        //     "successurl" => "https://business.b2press.com/test-api/garanti-return?action=success"
        //     "secure3dsecuritylevel" => "3D_PAY"
        //     "card_year" => "2028"
        //     "txntimeoutperiod" => "180"
        //     "terminaluserid" => "PROVAUT"
        //     "user_last_login_date" => "2024-10-07 10:51:29"
        //     "addcampaigninstallment" => "N"
        //     "user_zip_code" => "12345"
        //     "txntimestamp" => "1728298289"
        //     "card_name" => "Gunes Bizim"
        //     "installmentonlyforcommercialcard" => "N"
        //     "user_email" => "software-dev@unusualgrowth.com"
        //     "apiversion" => "512"
        // ]
    }
}
