<?php

namespace Unusualify\Payable\Services;

use Carbon\Carbon;
use Illuminate\Http\Request as HttpRequest;
use Unusualify\Payable\Models\Payment;

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

    $this->timeSpan = Carbon::now()->setTimezone('Europe/Istanbul')->format('YmdHis');
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

  public function startPaymentProcess(array $params)
  {
    $endpoint = 'ThreeDPayment';
    $hash = $this->generateHash();
    // dd($params);

    $this->headers = [
      'Content-Type' => 'application/json',
      'Accept' => '*/*'
    ];

    $this->params += $params;
    // $currency = PriceService::find($priceID)->currency;
    $payment = $this->createRecord(
        [
            'serviceName' => $this->serviceName,
            'order_id' => $this->params['order_id'],
            'currency_id' => $this->params['currency']->id,
            'amount' => $this->params['paid_price'],
            'email' => '', //Add email to data
            'price_id' => $this->params['price_id'],
            'payment_service_id' => $this->params['payment_service_id'],
            'installment' => $this->params['installment'],
            'parameters' => json_encode($this->params)
        ]
        // 
    );

    $returnUrl = route('payable.response').'?payment_service=teb-common-pos'.'&payment_id='.$payment->id;

    $data = [
      'clientId' => $this->clientId,
      'apiUser' => $this->apiUser,
      'rnd' => $this->rnd,
      'timeSpan' => $this->timeSpan,
      'hash' => $hash,
      'callbackUrl' => $returnUrl,
      'orderId' => $this->params['order_id'],
      'isCommission' => 0,
      'amount' => $this->params['paid_price'],
      'totalAmount' => $this->params['paid_price'],
      'currency' => $this->params['currency']->iso_4217_number,
      'installmentCount' => $this->params['installment'],
      'description' => '',
      'echo' => '',
      'extraParameters' => ''
    ];
    // dd($data, $this->params['currency']);
    $response = $this->postReq($this->url, $endpoint, json_encode($data), $this->headers,'raw');
    $responseObject = json_decode($response);
    // dd($responseObject, $response);
    if ($responseObject->Code == 0) {
        // dd($this->params);
      return $responseObject->ThreeDSessionId;
    }else{
      return json_decode($responseObject);
    }
  }

  public function pay(array $params)
  {
    $threeDSessionId = $this->startPaymentProcess($params);
    $endpoint = 'ProcessCardForm';
    // dd($params);
    $multipart = [
      [
        'name' => 'ThreeDSessionId',
        'contents' => $threeDSessionId,
      ],
      [
        'name' => 'CardHolderName',
        'contents' =>  $this->params['card_name'],
      ],
      [
        'name' => 'CardNo',
        'contents' =>   $this->params['card_no'],
      ],
      [
        'name' => 'ExpireDate',
        'contents' =>   $this->formatCardExpireDate($this->params['card_month'], $this->params['card_year']),
      ],
      [
        'name' => 'Cvv',
        'contents' =>   $this->params['card_cvv'],
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
  }
  
  public function handleResponse(HttpRequest $request){
    // dd($request);
    if($request->MdStatus == 1 && $request->BankResponseCode == '00'){

        $params = [
            'status' => 'success',
            'id' => $request->input('payment_id'),
            'service_payment_id' => $request->paymentId,
            'order_id' => $request->conversationId,
            'order_data' => $request->conversationData
        ];
        // dd($params['id']);
        $custom_fields = $this->updateRecord(
            $params['id'],
            'COMPLETED',
            $request->all()
        );
        // dd($custom_fields);
        
        $params['custom_fields'] = $custom_fields;
        // dd($params);
    }else{
        // dd($request->all());
        $payment = Payment::where('order_id',$request->input('OrderId'))->first();
        // dd($payment);
        $params = [
            'status' => 'fail',
            'id' => $payment->id,
            'payment_id' => $request->paymentId,
            'conversation_id' => $request->conversationId,
            'conversation_data' => $request->conversationData
        ];
        // dd($this->updateRecord(
        //     $params['id'],
        //     'COMPLETED',
        //     $request->all()
        // ));
        $response = $this->updateRecord(
            $params['id'],
            'COMPLETED',
            $request->all()
        );
        // dd($response);
        $params['custom_fields'] = $response;
    }

    return $this->generatePostForm($params, route(config('payable.return_url')));

    // [
    //   "ClientId" => "1000000031"
    //   "OrderId" => "ORD-6703c1081be43"
    //   "MdStatus" => "1"
    //   "ThreeDSessionId" => "P8A6AF94A670F48D396637ADABCF4AD200151EAE48E874FA0B80C5E3C1B6D9CDC"
    //   "BankResponseCode" => "00"
    //   "BankResponseMessage" => "İşlem onaylandı"
    //   "RequestStatus" => "1"
    //   "HashParameters" => "ClientId,ApiUser,OrderId,MdStatus,BankResponseCode,BankResponseMessage,RequestStatus"
    //   "Hash" => "0EnLRun4qsYVI6QTfWMW+VK4wBSqkOxPdBqDJVxuzRlqDwSbGTf75wo4uassPX+69zGCEF4ZBOZ+Qlw5xq568w=="
    // ]
    }
}
