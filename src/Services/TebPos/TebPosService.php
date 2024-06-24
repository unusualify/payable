<?php

namespace Unusualify\Payable\Services\TebPos;

use Unusualify\Payable\Services\RequestService;


class TebPosService extends RequestService{

  protected $merchantID;
  protected $storeKey;
  protected $params = [];
  public $processType;
  public $rnd;

  public function __construct($mode = null)
  {

    parent::__construct(
      envVar: '',
      apiProdKey: '',
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

    $this->url = $tempConfig['url'];
    $this->merchantID = $tempConfig['merchant_id'];
    $this->storeKey = $tempConfig['store_key'];

  }

  public function pay(array $params){
    $endpoint = 'fim/est3Dgate';

    $this->rnd = microtime();
    $this->params += $params;
    $this->processType = 'Auth';
    //array params should include following

    /*
    * orderId
    * amount
    * processType  = Auth 
    * installment = 0,
    */
    
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
    $response = $this->postReq($this->url,$endpoint,$data,[],'encoded');
    print($response);
    exit();
    // dd($response);
    

  }

  public function amountFormat($price){
    return number_format((float)$price, 2, ',', '');
    // dd($price, strval($price), number_format((float)$price, 2, ',', ''));
  }

  public function generateHash(){
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
    // dd(pack('H*', sha1(implode('', $map))));
    return base64_encode(pack('H*', sha1(implode('', $map))));
  }

}