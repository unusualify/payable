<?php

namespace Unusualify\Payable\Services\GarantiPos;

use Unusualify\Payable\Services\PaymentService;
use Unusualify\Priceable\Facades\PriceService;

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

  public function pay(array $params, int $priceID)
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

    $currency = PriceService::find($priceID)->currency;

    $this->createRecord((object)[
      'serviceName' => $this->serviceName,
      'paymentOrderId' => $this->params['orderid'],
      'currency_id' => $currency->id,
      'email' => '', //Add email to data
      'installment' => $this->params['txninstallmentcount'],
      'parameters' => $this->params
    ]);
    return print_r($resp);
  }

  public function generateHash()
  {

    $map = [
      $this->terminalID,
      $this->params['orderid'],
      $this->params['txnamount'],
      $this->params['txncurrencycode'],
      route('payable.garanti.return') . $this->returnQueries['success'],
      route('payable.garanti.return') . $this->returnQueries['error'],
      $this->params['txntype'],
      $this->params['txninstallmentcount'],
      $this->storeKey,
      $this->generateSecurityData()
    ];
    return strtoupper(hash('sha512', implode('', $map)));
  }

  public function generateSecurityData()
  {
    return strtoupper(sha1($this->provUserPassword . $this->terminalID));
  }
  
}
