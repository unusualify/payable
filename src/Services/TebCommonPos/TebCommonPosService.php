<?php

namespace Unusualify\Payable\Services\TebCommonPos;

use Carbon\Carbon;
use Unusualify\Payable\Services\RequestService;


class TebCommonPosService extends RequestService
{

protected $clientId;
protected $apiUser;
protected $apiPass;
public $rnd;
public $timeSpan;
protected $params = [];

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

    $this->timeSpan =
    Carbon::now()->setTimezone('Europe/Istanbul')->format('YmdHis');
    $this->rnd = (string) rand(100000, 999999);
    
  }

  public function setCredentials(){
    $this->setConfig();

    $this->mode = $this->config['mode'];
    $tempConfig = $this->config[$this->mode];
    
    $this->url = $tempConfig['url'];
    $this->clientId = $tempConfig['client_id'];
    $this->apiUser = $tempConfig['api_user'];
    $this->apiPass = $tempConfig['api_password'];

  }

  public function generateHash(){
    $hashString = $this->apiPass . $this->clientId . $this->apiUser . $this->rnd . $this->timeSpan;

    $hashingBytes = hash("sha512", ($hashString), true);
    $hash = base64_encode($hashingBytes);

    return $hash;
  }

  public function startPaymentProcess(array $params){
    $endpoint = 'ThreeDPayment';
    $hash = $this->generateHash();
    $returnUrl = route('payable.teb-common.return');

    $this->headers = [
      'Content-Type' => 'application/json',
      'Accept' => '*/*'
    ];

    $this->params += $params;
    
    
    $data = [
      'clientId' => $this->clientId,
      'apiUser' => $this->apiUser,
      'rnd' => $this->rnd,
      'timeSpan' => $this->timeSpan,
      'hash' => $hash,
      'callbackUrl' => $returnUrl,
      'orderId' => $this->params['orderid'],
      'isCommission' => $this->params['iscommission'],
      'amount' => $this->params['txnamount'],
      'totalAmount' => $this->params['txnamount'],
      'currency' => $this->params['txncurrencycode'],
      'installmentCount' => $this->params['txninstallmentcount'],
      'description' => '',
      'echo' => '',
      'extraParameters' => ''
    ];

    $response = $this->postReq($this->url, $endpoint, json_encode($data), $this->headers,'raw');
    $responseObject = json_decode($response);
    // dd($jsonResponse);
    // dd($response);
    if ($responseObject->Code == 0) {
      return $responseObject->ThreeDSessionId;
    }else{
      return json_decode($responseObject);
    }
    // dd($threeDSessionId);
  }

  public function pay(array $params){
    $threeDSessionId = $this->startPaymentProcess($params);
    $endpoint = 'ProcessCardForm';

    $multipart = [
      [
        'name' => 'ThreeDSessionId',
        'contents' => $threeDSessionId,
      ],
      [
        'name' => 'CardHolderName',
        'contents' =>  $this->params['cardname'],
      ],
      [
        'name' => 'CardNo',
        'contents' =>   $this->params['cardnumber'],
      ],
      [
        'name' => 'ExpireDate',
        'contents' =>   $this->formatCardExpireDate($this->params['cardexpiredatemonth'], $this->params['cardexpiredateyear']),
      ],
      [
        'name' => 'Cvv',
        'contents' =>   $this->params['cardcvv2'],
      ],
    ];
    $response = $this->postReq($this->url,$endpoint,$multipart,[],'multipart');
    print($response);
  }

  public function formatCardExpireDate($month, $year){
    return $month . substr($year, -2);
  }
}