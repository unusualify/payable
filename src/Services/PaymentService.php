<?php

namespace Unusualify\Payable\Services;


use Illuminate\Support\Str;
use Unusualify\Payable\Facades\Payment;

class PaymentService extends URequest{

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
    $redirect_url = null)
    {
    parent::__construct(
      mode : $this->mode,
      headers: $this->headers,
    );

    $this->root_path = base_path();
    $this->path = "{$this->root_path}/{$this->token_path}";

    $this->redirect_url = $redirect_url;
    $this->serviceName = str_replace('Service', '', class_basename($this));
  }

  public function getHeaders()
  {
    return $this->headers;
  }

  public function setConfig()
  {
    $this->config = config($this->getConfigName());
    // dd($this->config);
    $this->mode = $this->config['mode'];
  }

  public function getConfigName()
  {
    return 'payable' . '.services.' .strtolower(str_replace('Service', '', class_basename($this)));
  }

  function createRecord(object $data)
  {
    // dd($data->price);
    $payment = Payment::create(
      [
        'payment_gateway' => $data->payment_gateway,
        'order_id' => $data->order_id,
        'price' => $data->price,
        'currency_id' => $data->currency_id,
        'email' => $data->email,
        // 'installment' => $data->installment,
        'parameters' => json_encode($data),
      ]
    );
    return $payment;
  }
  static function updateRecord($order_id, $status, $response)
  {
    // dd($order_id);
    // dd($response);
    return Payment::where('order_id' ,$order_id)
            ->update([
              'status' => $status,
              'response' => $response,
              'parameters' => null,
            ]);
  }

  public function setMode($mode)
  {
    $this->mode = $mode;
  }
}
