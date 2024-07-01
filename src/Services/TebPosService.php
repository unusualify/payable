<?php

namespace Unusualify\Payable\Services;

use Unusualify\Priceable\Facades\PriceService;

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

  public function pay(array $params, int $priceID)
  {
    $endpoint = 'fim/est3Dgate';

    $this->rnd = microtime();
    $this->params += $params;
    $this->processType = 'Auth';
    
    $hash = $this->generateHash();
    $data = [
      'pan' => $this->params['cardnumber'],
      'Ecom_Payment_Card_ExpDate_Month' => $this->params['cardexpiredatemonth'],
      'Ecom_Payment_Card_ExpDate_Year' => $this->params['cardexpiredateyear'],
      'cv2' => $this->params['cardcvv2'],
      'amount' => $this->params['txnamount'],
      'cardType' => '',
      'clientid' => $this->merchantID,
      'oid' => $this->params['orderid'],
      'okUrl' => route('payable.teb.return') . $this->returnQueries['success'],
      'failUrl' => route('payable.teb.return') . $this->returnQueries['error'],
      'rnd' => $this->rnd,
      'hash' => $hash,
      'islemtipi' => $this->processType,
      'taksit' => $this->params['txninstallmentcount'],
      'currency' => $this->params['txncurrencycode'],
      'storetype' => '3d_pay_hosting',
      'lang' => $this->params['lang'],
      'firmaadi' => $this->params['companyname'],
    ];
    $currency = PriceService::find($priceID)->currency;

    $this->createRecord((object)[
      'serviceName' => $this->serviceName,
      'paymentOrderId' => $this->params['orderid'],
      'currency_id' => $currency->id,
      'email' => '', //Add email to data
      'installment' => $this->params['txninstallmentcount'],
      'parameters' => $data
    ]);
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
      $this->params['orderid'],
      $this->params['txnamount'],
      route('payable.teb.return'). $this->returnQueries['success'],
      route('payable.teb.return') . $this->returnQueries['error'],
      $this->processType,
      $this->params['txncurrencycode'],
      $this->rnd,
      $this->storeKey
    ];
    return base64_encode(pack('H*', sha1(implode('', $map))));
  }

}