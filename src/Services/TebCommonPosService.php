<?php

namespace Unusualify\Payable\Services;

use Carbon\Carbon;
use Unusualify\Priceable\Facades\PriceService;

class TebCommonPosService extends PaymentService
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
      headers: $this->headers,
    );

    $this->setCredentials();

    $this->timeSpan =
    Carbon::now()->setTimezone('Europe/Istanbul')->format('YmdHis');
    $this->rnd = (string) rand(100000, 999999);
    
  }

  public function setCredentials()
  {
    $this->setConfig();

    $this->mode = $this->config['mode'];
    $tempConfig = $this->config[$this->mode];
    
    $this->url = $tempConfig['url'];
    $this->clientId = $tempConfig['client_id'];
    $this->apiUser = $tempConfig['api_user'];
    $this->apiPass = $tempConfig['api_password'];

  }

  public function generateHash()
  {
    $hashString = $this->apiPass . $this->clientId . $this->apiUser . $this->rnd . $this->timeSpan;

    $hashingBytes = hash("sha512", ($hashString), true);
    $hash = base64_encode($hashingBytes);

    return $hash;
  }

  public function startPaymentProcess(array $params, int $priceID)
  {
    $endpoint = 'ThreeDPayment';
    $hash = $this->generateHash();
    $returnUrl = route('payable.teb-common.return');

    $this->headers = [
      'Content-Type' => 'application/json',
      'Accept' => '*/*'
    ];

    $this->params += $params;
    $currency = PriceService::find($priceID)->currency;
    
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
      'currency' => $currency->iso_4217, 
      'installmentCount' => $this->params['txninstallmentcount'],
      'description' => '',
      'echo' => '',
      'extraParameters' => ''
    ];

    $response = $this->postReq($this->url, $endpoint, json_encode($data), $this->headers,'raw');
    $responseObject = json_decode($response);

    if ($responseObject->Code == 0) {
      $this->createRecord(
        [
          'payment_gateway' => $this->serviceName,
          'paymentOrderId' => $data['orderId'],
          'amount' => $data['amount'],
          'currencyId' => $data['currency'],
          'email' => $this->params['email'],
          'installment' => $data['installmentCount'],
          'parameters' => $this->params
        ]
      );
      return $responseObject->ThreeDSessionId;
    }else{
      return json_decode($responseObject);
    }
  }

  public function pay(array $params, int $priceID)
  {
    $threeDSessionId = $this->startPaymentProcess($params, $priceID);
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

  public function formatCardExpireDate($month, $year)
  {
    return $month . substr($year, -2);
  }

  public function hydrateParams(array $params)
  {
    //TODO: 
  }
}