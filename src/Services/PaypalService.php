<?php

namespace Unusualify\Payable\Services;

use Exception;
use GuzzleHttp\Utils;
use RuntimeException;
use Unusualify\Payable\PayPal\Str;
use Unusualify\Payable\Services\Paypal\Traits\PaypalAPI;
use Unusualify\Payable\Services\Paypal\Traits\PayPalVerifyIPN;
use Unusualify\Priceable\Facades\PriceService;
use Unusualify\Priceable\Models\Price;

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

    $this->getAccessToken();
    

  }

  public function doPaypalRequest(bool $decode = true)
  {

    try {
      if($this->verb == 'post'){
        if(!isset($this->options['request_body'])){
          $this->options['request_body'] = [];
        }
        // dd($this->options['request_body'], $this->type);
        $response = $this->postReq(
          $this->url,
          $this->apiEndPoint,
          $this->options['request_body'],
          $this->headers,
          $this->type,
          'test'
        );
      }else{ //Get request 
        $response = $this->getReq(
          $this->url,
          $this->apiEndPoint,
          [],
          $this->headers
        );
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
    $resp =  json_decode($this->doPayPalRequest());
    // dd($resp, $resp->id);
    // dd($data);
    $currency = Price::find($priceID)->currency;
    $this->createRecord((object)[
        'payment_gateway' => $this->serviceName,
        'order_id' => $resp->id,
        'price' => $data['purchase_units'][0]['amount']['value'],
        'currency_id' => $currency->id,
        'email' => $data['payment_source']['paypal']['email_address'],
        'installment' => '0',
        'parameters' => json_encode($data),
      ]
    );
    return $resp;
  }

  public function capturePayment($order_id, array $data = []){
    $this->apiEndPoint = "v2/checkout/orders/{$order_id}/capture";

    $this->options['request_body'] = (object) $data;

    $this->verb = 'post';

    $this->type = 'json';
    // dd($order_id);
    // dd($this->doPayPalRequest());
    $resp = json_decode($this->doPayPalRequest());
    // dd($resp);
    $data = [
      'payment_source' => $resp->payment_source,
      'purchase_units' => $resp->purchase_units,
      'payer' => $resp->payer,
      'links' => $resp->links
    ];
    // dd($data);
      $this->updateRecord(
        $resp->id,
        'COMPLETED',
        $data
      );

    return $resp;
    // return $this->doPayPalRequest();
  }

  public function refund(array $params)

  // string $capture_id, string $invoice_id, float $amount, string $note, $priceID
  {
    $this->apiEndPoint = "v2/payments/captures/{$params['capture_id']}/refund";
    $this->verb = 'post';
    $this->type = 'raw';

    // $currency = Price::find($params['priceID'])->currency;
    // dd($currency);
    // $this->options['request_body'] = json_encode([
    //   'amount' => [
    //     'value' => $params['amount'],
    //     'currency_code' => $currency->iso_4217
    //   ],
    //   'invoice_id' => $params['order_id'],
    //   'note_to_payer' => "Refund of {$params['order_id']}"
    // ]);
    $this->options['request_body'] = '{}';
    // dd($this->options['request_body']);
    $this->headers['Content-Type'] = 'application/json';
    
    // dd($this->options);
    $resp =  $this->doPayPalRequest();

    if(json_decode($resp)->status == 'COMPLETED'){
      $this->updateRecord(
        $params['order_id'],
        'REFUNDED',
        $resp
      );
      
      return true;
    }else{
      return false;
    }

    // dd($resp);
  }

  public function showFromSource($orderId){

      $this->apiEndPoint = "v2/checkout/orders/{$orderId}";
      $this->headers['Content-Type'] = 'application/json';
      $this->verb = 'get';
      $resp = $this->doPaypalRequest();
    
      return $resp;
  }
}
