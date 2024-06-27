<?php

namespace Unusualify\Payable\Services;


use Illuminate\Support\Str;
use Unusualify\Payable\Facades\Payment;

class RequestService extends URequest{

  public $mode;

  protected $url;

  protected $root_path;

  protected $token_path;

  protected $path;

  protected $token_refresh_time; //by minute
  
  protected $redirect_url;

  protected $config;

  public $serviceName;

  

  protected $headers = [ 
    'Authorization' => 'Bearer',
    'Content-Type' => 'application/json',
  ];

  public function __construct(    
    $headers = null,

    $redirect_url = null
  ){
    parent::__construct(
      mode : $this->mode,
      headers: $this->headers,
    );

    $this->root_path = base_path();
    $this->path = "{$this->root_path}/{$this->token_path}";

    $this->redirect_url = $redirect_url;
    $this->serviceName = str_replace('Service', '', class_basename($this));
  }

  public function getHeaders(){
    return $this->headers;
  }

  function setConfig(){
    $this->config = config($this->getConfigName());
    $this->mode = $this->config['mode'];
  }

  function getConfigName(){
    return 'payable' . '.services.' .strtolower(str_replace('Service', '', class_basename($this)));
  }

  function createRecord(object $data){

    $payment = Payment::create(
      [
        'payment_gateway' => $data->serviceName,
        'order_id' => $data->paymentOrderId,
        'price' => $data->amount,
        'currency_id' => $data->currencyId,
        'email' => $data->email,
        'installment' => $data->installment,
        'parameters' => json_encode($data),
      ]
    );
    return $payment;
  }
  static function updateRecord($order_id, $status, $response){
    return Payment::where('order_id' ,$order_id)
            ->update([
              'status' => $status,
              'response' => $response,
              'parameters' => null,
            ]);
  }

  public function setMode($mode){
    
  }

  public function setLive(){

  }
  
  public function setSandbox(){

  }
}
