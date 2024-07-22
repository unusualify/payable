<?php

namespace Unusualify\Payable\Services;

use Unusualify\Payable\Models\Payment;
use Unusualify\Payable\Payable;
use Unusualify\Payable\Services\Iyzico\Requests\CreateThreedsPaymentRequest;
use Unusualify\Payable\Services\RequestService;
use Unusualify\Payable\Services\Iyzico\Models\Address;
use Unusualify\Payable\Services\Iyzico\Models\BasketItem;
use Unusualify\Payable\Services\Iyzico\Models\BasketItemType;
use Unusualify\Payable\Services\Iyzico\Models\Buyer;
use Unusualify\Payable\Services\Iyzico\Requests\CreatePaymentRequest;
use Unusualify\Payable\Services\Iyzico\Models\Currency;
use Unusualify\Payable\Services\Iyzico\Models\PaymentCard;
use Unusualify\Payable\Services\Iyzico\Models\RequestStringBuilder;
use Unusualify\Payable\Services\Iyzico\Requests\CreateCancelRequest;
use Unusualify\Payable\Services\Iyzico\Requests\CreateRefundRequest;
use Unusualify\Payable\Services\Iyzico\Requests\CreateRefundRequestV2;
use Unusualify\Payable\Services\Iyzico\Requests\ReportingPaymentDetailRequest;
use Unusualify\Payable\Services\Iyzico\Requests\Request;
use Unusualify\Priceable\Models\Price;

class IyzicoService extends PaymentService
{


  public $prodUrl;

  public $apiProdKey;

  public $apiProdSecret;

  public $merchantId;

  protected $url;

  protected $root_path;

  protected $token_refresh_time = 60; //by minute

  protected $redirect_url = null;



  protected $headers = [
    'Authorization' => 'Bearer',
    'Content-Type' => 'application/json',
  ];
  
   //TODO: Subscription service will be added

  public function __construct($mode = null)
  {

    parent::__construct(
      headers: $this->headers
    );

    $this->root_path = base_path();
    $this->setCredentials();
  }

  public function generateHash($apiKey, $secretKey, $randomString, $request)
  {
    // dd($request->toPKIRequestString());
    $hashStr = $apiKey . $randomString . $secretKey . $request->toPKIRequestString();
    return base64_encode(sha1($hashStr, true));
  }

  public function generateHeaders($request = null)
  {
    $header = array(
      "Accept" =>  "application/json",
      "Content-type" => "application/json",
    );

    $rnd = uniqid();
    $header["Authorization"] = $this->prepareAuthorizationString($request, $rnd);
    $header["x-iyzi-rnd"] = $rnd;
    $header["x-iyzi-client-version"] = "iyzipay-php-2.0.54";
    $this->headers = $header;
    return $header;
  }

  public function generateHeadersV2($request = null, $uri)
  {
    $header = array(
      "Accept" =>  "application/json",
      "Content-type" => "application/json",
    );
    $rnd = uniqid();
    // dd($uri,RequestStringBuilder::requestToStringQuery($request, 'reporting'));
    $header["Authorization"] = $this->prepareAuthorizationStringV2(null, $rnd, ($uri . RequestStringBuilder::requestToStringQuery($request, 'reporting')));
    $header["x-iyzi-rnd"] = $rnd;
    $header["x-iyzi-client-version"] = "iyzipay-php-2.0.54";
    $this->headers = $header;

    return $header;
  }

  protected function prepareAuthorizationString($request, $rnd)
  {
    // dd($request);
    $authContent = $this->generateHash($this->apiKey, $this->apiSecret, $rnd, $request);
    return vsprintf("IYZWS %s:%s", array($this->apiKey, $authContent));
  }

  public function setCredentials()
  {
    $this->setConfig();
    $tempConfig = $this->config[$this->mode];

    $this->url = $tempConfig['url'];
    $this->apiKey = $tempConfig['api_key'];
    $this->apiSecret = $tempConfig['api_secret'];
    $this->merchantId = $this->config['merchant_id'];
    $this->token_refresh_time = $this->config['token_refresh_time'];
    $this->redirect_url = route('payable.iyzico.return');
  }

  public function prepareAuthorizationStringV2(Request $request = null, $randomString, $uri)
  {
    $hashStr = "apiKey:" . $this->apiKey . "&randomKey:" . $randomString . "&signature:" . $this->getHmacSHA256Signature($uri, $this->apiSecret, $randomString, $request);
    // dd($hashStr);
    $hashStr = base64_encode($hashStr);

    return 'IYZWSv2'. ' ' .$hashStr;
  }

  public function getHmacSHA256Signature($uri, $secretKey, $randomString, Request $request = null)
  {
    $dataToEncrypt = $randomString . self::getPayload($uri, $request);
    // dd($dataToEncrypt);
    $hash = hash_hmac('sha256', $dataToEncrypt, $secretKey, true);
    $token = bin2hex($hash);

    return $token;
  }

  public function getPayload($uri, Request $request = null)
  {

    $startNumber  = strpos($uri, '/v2');
    $endNumber    = strpos($uri, '?');
    if (strpos($uri, "subscription") || strpos($uri, "ucs")) {
      $endNumber = strlen($uri);
      if (strpos($uri, '?')) {
        $endNumber    = strpos($uri, '?');
      }
    }
    $endNumber -=  $startNumber;

    $uriPath      =  substr($uri, $startNumber, $endNumber);
    // dd(!empty($request), $request->toJsonString(), $uriPath);
    if (!empty($request) && $request->toJsonString() != '[]')
      $uriPath = $uriPath . $request->toJsonString();

    // dd($uriPath, $uri);

    return $uriPath;
  }

  public function pay(array $params)
  {
    $endpoint = "/payment/3dsecure/initialize";

    $request = new CreatePaymentRequest();
    $request->setLocale($params['locale']);
    $request->setConversationId($params['order_id']);
    $request->setPrice($params['price']);
    $request->setPaidPrice($params['paidPrice']);
    $request->setCurrency($params['currency']->iso_4217);
    $request->setInstallment($params['installment']);
    $request->setBasketId($params['basketId']);
    $request->setPaymentChannel("WEB");
    $request->setPaymentGroup($params['paymentGroup']);
    $request->setCallbackUrl($this->redirect_url);

    $paymentCard = new PaymentCard();
    $paymentCard->setCardHolderName($params['paymentCard']['cardHolderName']);
    $paymentCard->setCardNumber($params['paymentCard']['cardNumber']);
    $paymentCard->setExpireMonth($params['paymentCard']['expireMonth']);
    $paymentCard->setExpireYear($params['paymentCard']['expireYear']);
    $paymentCard->setCvc($params['paymentCard']['cvc']);
    $paymentCard->setRegisterCard(0);
    $request->setPaymentCard($paymentCard);

    $buyer = new Buyer();
    $buyer->setId($params['buyer']['id']);
    $buyer->setName($params['buyer']['name']);
    $buyer->setSurname($params['buyer']['surname']);
    $buyer->setGsmNumber($params['buyer']['gsmNumber']);
    $buyer->setEmail($params['buyer']['email']);
    $buyer->setIdentityNumber($params['buyer']['id']);
    $buyer->setLastLoginDate($params['buyer']['lastLoginDate']);
    $buyer->setRegistrationDate($params['buyer']['registrationDate']);
    $buyer->setRegistrationAddress($params['buyer']['registrationAddress']);
    $buyer->setIp($params['buyer']['ip']);
    $buyer->setCity($params['buyer']['city']);
    $buyer->setCountry($params['buyer']['country']);
    $buyer->setZipCode($params['buyer']['zipCode']);
    $request->setBuyer($buyer);

    $shippingAddress = new Address();
    $shippingAddress->setContactName($params['shippingAddress']['contactName']);
    $shippingAddress->setCity($params['shippingAddress']['city']);
    $shippingAddress->setCountry($params['shippingAddress']['country']);
    $shippingAddress->setAddress($params['shippingAddress']['address']);
    $shippingAddress->setZipCode($params['shippingAddress']['zipCode']);
    $request->setShippingAddress($shippingAddress);

    $billingAddress = new Address();
    $billingAddress->setContactName($params['billingAddress']['contactName']);
    $billingAddress->setCity($params['billingAddress']['city']);
    $billingAddress->setCountry($params['billingAddress']['country']);
    $billingAddress->setAddress($params['billingAddress']['address']);
    $billingAddress->setZipCode($params['billingAddress']['zipCode']);
    $request->setBillingAddress($billingAddress);

    $basketItems = array();
    foreach ($params['basketItems'] as $item) {
      $basketItem = new BasketItem();
      $basketItem->setId($item['id']);
      $basketItem->setName($item['name']);
      $basketItem->setCategory1($item['category1']);
      $basketItem->setCategory2($item['category2']);
      $basketItem->setItemType($item['itemType']);
      $basketItem->setPrice($item['price']);
      array_push($basketItems, $basketItem);
    }
    $request->setBasketItems($basketItems);
    $recordParams = $this->hydrateParams($params);
    $this->createRecord(
      $recordParams  
    );

    $resp = $this->postReq($this->url, $endpoint, $request->toJsonString(), $this->generateHeaders($request), 'raw');
    // dd($resp);
    $threeDForm = base64_decode(json_decode($resp)->threeDSHtmlContent); 
    # print result
    print($threeDForm);
    exit;
  }

  public function cancel(array $params)
  {
    $endpoint = "/payment/cancel";

    $request = new CreateCancelRequest();

    $request->setLocale($params['locale']);
    $request->setIp($params['ip']);
    $request->setPaymentId($params['payment_id']);
    $request->setConversationId($params['conversation_id']);
    
    // dd($this->generateHeaders($request));

    $resp = $this->postReq($this->url, $endpoint, $request->toJsonString(), $this->generateHeaders($request), 'raw');

    if ((json_decode($resp))->status == 'success') {
      return $this->updateRecord(
        $params['conversation_id'],
        'CANCELLED',
        $resp
      );
    }
    
    dd($resp, $request->toJsonString(), $this->headers);
  }
  

  public function completePayment($params)
  {
    $endpoint = '/payment/3dsecure/auth';
    $request = new CreateThreedsPaymentRequest();

    $request->setPaymentId($params['payment_id']);
    $request->setConversationId($params['conversation_id']);
    $request->setConversationData($params['conversation_data']);

    $resp = $this->postReq($this->url, $endpoint, $request->toJsonString(), $this->generateHeaders($request), 'raw');

    if((json_decode($resp))->status == 'success'){
      return $this->updateRecord(
        $params['conversation_id'],
        'COMPLETED',
        $resp
      );
      // dd($resp);
      return true;
    }else{
      return false;
    }
    // dd('here');

  }

  public function refund($params){
    $endpoint = '/payment/refund';

    $request = new CreateRefundRequest();
    
    $request->setPaymentTransactionId($params['payment_id']);
    $request->setPrice($params['price']);

    // dd($request, $request->toJsonString(), $this->generateHeaders($request));

    $resp = $this->postReq($this->url, $endpoint, $request->toJsonString(), $this->generateHeaders($request), 'raw');

    if ((json_decode($resp))->status == 'success') {
      return $this->updateRecord(
        $params['conversation_id'],
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
    $endpoint = 'v2/reporting/payment/details';

    $request = new ReportingPaymentDetailRequest();

    $order = Payment::where('order_id',$orderId)->get()[0];
    $paymentId = json_decode($order->response)->paymentId;
    $request->setPaymentConversationId($paymentId);
    $request->setConversationId($orderId);
    $request->setLocale('tr');
    // dd($this->genereateHeadersV2($request, ($this->url . $endpoint)));
    $resp = $this->getReq($this->url,$endpoint,[
      'paymentId'=>$paymentId,
    ], $this->generateHeadersV2($request, ($this->url . $endpoint)));

    return $resp;
  }

  public function hydrateParams(array $params)
  {
    $recordParams = [
      'amount' => $params['price'],
      'email' => $params['buyer']['email'],
      'installment' => $params['installment'],
      'payment_service_id' => $params['payment_service_id'],
      'price_id' => $params['price_id'],
      'parameters' => json_encode($params),
      'order_id' => $params['order_id']
    ];

    return $recordParams;
      
  }
  
}
