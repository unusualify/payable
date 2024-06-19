<?php

namespace Unusualify\Payable\Services\GarantiPos;

use Unusualify\Payable\Services\RequestService;



class GarantiPosService extends RequestService{

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
    parent::__construct(
      envVar: 'GARANTI_POS_MODE',
      apiProdKey: 'GARANTI_POS_API_KEY',
      apiProdSecret: $this->apiProdSecret,
      apiTestSecret: '',
      apiTestKey: '',
      prodUrl: $this->prodUrl,
      headers: $this->headers,
    );

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

        "successurl" => route('payable.test.garanti.return') . '?action=success',
        "errorurl" => route('payable.test.garanti.return') . '?action=error',
     
        "txntimeoutperiod" => $this->timeOutPeriod,
        "addcampaigninstallment" => $this->addCampaignInstallment,
        "totallinstallmentcount" => $this->totalInstallamentCount,
        "installmentonlyforcommercialcard" =>$this->installmentOnlyForCommercialCard,
    ];
    // dd($this);
  }

  public function pay(array $params){
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
    print_r($resp);
    exit;
    // dd($resp);
    // print_r($resp);
    // dd($resp);
    // "cardname" => "Güneş Bizim",
    // "cardnumber" => "4543 6042 7860 9073",
    // "cardexpiredatemonth" => "08",
    // "cardexpiredateyear" => "2028",
    // "cardcvv2" => "372",
    // "companyname" => "OLMADIK PROJELER",
    // "orderid" => "61f788af7a414",
    // "customeremailaddress" => "oguz.bukcuoglu@gmail.com",
    // "customeripaddress" => "172.19.0.1",
    // "txnamount" => "1000",
    // "txncurrencycode" => 949,
    // "txninstallmentcount" => "",
    // "lang" => "tr",
    // "txntimestamp" => 1718722545,
    // "secure3dhash" => "9B8A27AA6621988C54F3A84A332706280681D334843EC4CE03D2E094F713CBEDA436CEF18EC38263EB750A5A3D70F7969DAFB18DF9F3313178E56785DA8E5EC5"

  }

  public function generateHash(){

    $map = [
      $this->terminalID,
      $this->params['orderid'],
      $this->params['txnamount'],
      $this->params['txncurrencycode'],
      $this->params['successurl'],
      $this->params['errorurl'],
      $this->params['txntype'],
      $this->params['txninstallmentcount'],
      $this->storeKey,
      $this->generateSecurityData()
    ];
    // dd($this->generateSecurityData());
    return strtoupper(hash('sha512', implode('', $map)));
  }

  public function generateSecurityData(){
    return strtoupper(sha1($this->provUserPassword . $this->terminalID));
  }
  
}
