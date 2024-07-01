<?php

namespace Unusualify\Payable\Services;

use Unusualify\Payable\Services\RequestService;
use Unusualify\Payable\Services\Iyzico\Models\Address;
use Unusualify\Payable\Services\Iyzico\Models\BasketItem;
use Unusualify\Payable\Services\Iyzico\Models\BasketItemType;
use Unusualify\Payable\Services\Iyzico\Models\Buyer;
use Unusualify\Payable\Services\Iyzico\Requests\CreatePaymentRequest;
use Unusualify\Payable\Services\Iyzico\Models\Currency;
use Unusualify\Payable\Services\Iyzico\Models\PaymentCard;
use Unusualify\Priceable\Facades\PriceService;

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

  public function __construct($mode = null)
  {

    parent::__construct(
      headers: $this->headers
    );

    $this->root_path = base_path();
    $this->setCredentials();
  }

  public function generateHash($apiKey, $secretKey, $randomString, CreatePaymentRequest $request)
  {
    $hashStr = $apiKey . $randomString . $secretKey . $request->toPKIRequestString();
    return base64_encode(sha1($hashStr, true));
  }

  public function generateHeaders(CreatePaymentRequest $request)
  {
    $header = array(
      "Accept" =>  "application/json",
      "Content-type" => "application/json",
    );

    $rnd = uniqid();
    $header["Authorization"] = $this->prepareAuthorizationString($request, $rnd);
    $header["x-iyzi-rnd"] = $rnd;
    $header["x-iyzi-client-version"] = "iyzipay-php-2.0.54";

    return $header;
  }

  protected function prepareAuthorizationString(CreatePaymentRequest $request, $rnd)
  {
    $authContent = $this->generateHash($this->apiKey, $this->apiSecret, $rnd, $request);
    return vsprintf("IYZWS %s:%s", array($this->apiKey, $authContent));
  }

  public function pay(array $params, int $priceID)
  {
    $endpoint = "/3dsecure/initialize";

    $currency = PriceService::find($priceID)->currency;
    

    # create request class
    $request = new CreatePaymentRequest();
    $request->setLocale($params['locale']);
    $request->setConversationId($params['orderId']);
    $request->setPrice($params['price']);
    $request->setPaidPrice($params['paidPrice']);
    $request->setCurrency($currency->iso_code);
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
    $buyer->setIdentityNumber($params['buyer']['']);
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
    foreach($params['basketItems'] as $item){
      $basketItem = new BasketItem();
      $basketItem->setId($params['basketItem']['id']);
      $basketItem->setName($params['basketItem']['name']);
      $basketItem->setCategory1($params['basketItem']['category1']);
      $basketItem->setCategory2($params['basketItem']['category2']);
      $basketItem->setItemType($params['basketItem']['itemType']);
      $basketItem->setPrice($params['basketItem']['price']);
    }
    $this->createRecord((object)[
      'payment_gateway' => $this->serviceName,
      'order_id' => $params['orderId'],
      'price' => '',
      'currency_id' => $currency->id,
      'email' => '',
      'installment' => '',
      'parameters' => json_encode('')
    ]);
    $resp = $this->postReq($this->url, $endpoint, $request->toJsonString(), $this->generateHeaders($request), 'raw');

    $threeDForm = base64_decode(json_decode($resp)->threeDSHtmlContent); 
    # print result
    print($threeDForm);
    exit;
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
  
}
