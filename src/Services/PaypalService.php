<?php

namespace Unusualify\Payable\Services;

use Exception;
use GuzzleHttp\Utils;
use RuntimeException;
use Unusualify\Payable\PayPal\Str;
use Unusualify\Payable\Services\Paypal\Traits\PaypalAPI;
use Unusualify\Payable\Services\Paypal\Traits\PayPalVerifyIPN;
use Unusualify\Priceable\Facades\PriceService;

class PaypalService extends PaymentService
{
  use Paypal\Traits\PaypalConfig;

  use PayPalVerifyIPN;
  use PaypalAPI;

  protected $options;
  protected $httpBodyParam;
  protected $verb;
  protected $type;
  public $apiEndPoint;
  /**
   * PayPal constructor.
   *
   * @param array $config
   *
   * @throws Exception
   */

  public function __construct(array $config = [])
  {
    // Setting PayPal API Credentials
    // Manage setConfig functio based on the needs of URequest class
    $this->getConfigName();
    $this->setConfig($config);
    $this->url = $this->config['api_url'];
    $this->httpBodyParam = 'form_params';
    $this->options = [];
    $this->setRequestHeader('Accept', 'application/json');

    parent::__construct(
      $this->mode,
    );
    

  }

  public function doPaypalRequest(bool $decode = true){

    try {
      if($this->verb == 'post'){
        $response = $this->postReq(
          $this->url,
          $this->apiEndPoint,
          $this->options['request_body'],
          $this->headers,
          $this->type
        );
      }else{ //Get request 
        
      }
      return $response;
    } catch (RuntimeException $t) {
      $error = ($decode === false) || (Str::isJson($t->getMessage()) === false) ? $t->getMessage() : Utils::jsonDecode($t->getMessage(), true);

      return ['error' => $error];
    }

  }

  public function pay(array $data , int $priceID)
  {
    $this->apiEndPoint = 'v2/checkout/orders';

    $this->options['request_body'] = $data;
    $this->type = 'json';
    $this->verb = 'post';
    $resp =  $this->doPayPalRequest();
    $currency = PriceService::find($priceID)->currency;
    $this->createRecord((object)[
        'payment_gateway' => $this->serviceName,
        'order_id' => $resp->id,
        'price' => $data['purchase_units']['amount']['value'],
        'currency_id' => $currency->id,
        'email' => $data['payer']['email_address'],
        'installment' => '0',
        'parameters' => json_encode($data),
      ]
    );
  }
}
