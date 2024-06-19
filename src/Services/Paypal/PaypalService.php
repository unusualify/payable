<?php

namespace Unusualify\Payable\Services\Paypal;

use Exception;
use GuzzleHttp\Utils;
use RuntimeException;
use Unusualify\Payable\PayPal\Str;
use Unusualify\Payable\Services\Paypal\Traits\PaypalAPI;
// use Srmklive\PayPal\Traits\PayPalRequest as PayPalAPIRequest;
use Unusualify\Payable\Services\Paypal\Traits\PayPalVerifyIPN;
use Unusualify\Payable\Services\RequestService;

class PaypalService extends RequestService
{
  use Traits\PaypalConfig;

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
    // dd($this->config);
    $this->httpBodyParam = 'form_params';

    $this->options = [];

    $this->setRequestHeader('Accept', 'application/json');

    // dd($this->mode);
    parent::__construct(
      $this->mode,
    );
    

  }

  //  Recreate doPaypalRequest function in the PaypalService class

  public function doPaypalRequest(bool $decode = true){
    // dd($this->options);
    // $this->generateBasicAuthHeaders();
    try {
      /** Header must contain :
        * Authorization: Basic base64_encode('CLIENT_ID:CLIENT_SECRET') 
        * body : {'grant_type':'client_credentials'}
        * content-type: application/x-www-form-urlencoded (it equals to encoded option in ) 
      **/
      // dd($this->headers);
      // dd($this->verb);
      // dd($this->options);
      if($this->verb == 'post'){
        // dd($this->headers);
        // dd($this->apiEndPoint);
        // dd($this);
        // if(isset($this->options['request_body']['intent'])){
        //   dd($this->options['request_body'], $this->headers, $this->type);
        // }
        $response = $this->postReq(
          $this->config['api_url'],
          $this->apiEndPoint,
          $this->options['request_body'],
          $this->headers,
          $this->type
        );
      }else{ //Get request
        
      }
      
      // $this->apiUrl = collect([$this->config['api_url'], $this->apiEndPoint])->implode('/');

      // Perform PayPal HTTP API request.
      // $response = $this->makeHttpRequest();
      return $response;
      // return ($decode === false) ? $response->getContents() : Utils::jsonDecode($response, true);
    } catch (RuntimeException $t) {
      $error = ($decode === false) || (Str::isJson($t->getMessage()) === false) ? $t->getMessage() : Utils::jsonDecode($t->getMessage(), true);

      return ['error' => $error];
    }
      // Perform PayPal HTTP API request.
      //$response = $this->makeHttpRequest();
  }
  public function makeHttpRequest(){
    //Not sure if it's needed anymore
  }
}
