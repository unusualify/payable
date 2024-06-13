<?php

namespace Unusualify\Payable\Services\Iyzico;

use Unusualify\Payable\Services\RequestService;
use Unusualify\Payable\Services\Iyzico\Models\Address;
use Unusualify\Payable\Services\Iyzico\Models\BasketItem;
use Unusualify\Payable\Services\Iyzico\Models\Buyer;
use Unusualify\Payable\Services\Iyzico\Requests\CreatePaymentRequest;
use Unusualify\Payable\Services\Iyzico\Models\Currency;
use Unusualify\Payable\Services\Iyzico\Models\PaymentCard;

class IyzicoService extends RequestService
{


  protected $prodUrl = 'https://sandbox-api.iyzipay.com/payment';

  protected $apiProdKey = 'sandbox-zwhSW4JMsA3GG3aAI4SjnLcyCEQHYtbA';

  protected $apiProdSecret = 'sandbox-VS9QsDF9ECiagNLRV8YfoK766F8n76P8';

  protected $merchantId = '3395857';

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
      envVar: 'IYZICO_MODE',
      apiProdKey: $this->apiProdKey,
      apiProdSecret:$this->apiProdSecret,
      prodUrl: $this->prodUrl,
      headers: $this->headers
    );

    $this->root_path = base_path();
  }

  public function generateHash($apiKey, $secretKey, $randomString, CreatePaymentRequest $request)
  {
    $hashStr = $apiKey . $randomString . $secretKey . $request->toPKIRequestString();
    return base64_encode(sha1($hashStr, true));
  }

  public function generateHeaders(CreatePaymentRequest $request){
    $header = array(
      "Accept" =>  "application/json",
      "Content-type" => "application/json",
    );

    $rnd = uniqid();
    $header["Authorization"] = $this->prepareAuthorizationString($request, $rnd);
    $header["x-iyzi-rnd"] = $rnd;
    $header["x-iyzi-client-version"] = "iyzipay-php-2.0.54";
    // array_push($header, "Authorization: " . $this->prepareAuthorizationString($request, $rnd));
    // array_push($header, "x-iyzi-rnd: " . $rnd);
    // array_push($header, "x-iyzi-client-version: " . "iyzipay-php-2.0.54");

    return $header;
  }

  protected function prepareAuthorizationString(CreatePaymentRequest $request, $rnd)
  {
    $authContent = $this->generateHash($this->apiKey, $this->apiSecret, $rnd, $request);
    return vsprintf("IYZWS %s:%s", array($this->apiKey, $authContent));
  }

  public function initThreeDS()
  {
    $endpoint = "/3dsecure/initialize";

    # create request class
    $request = new CreatePaymentRequest();
    $request->setLocale('tr');
    $request->setConversationId("123456789");
    $request->setPrice("1");
    $request->setPaidPrice("1.2");
    $request->setCurrency(Currency::TL);
    $request->setInstallment(1);
    $request->setBasketId("B67832");
    $request->setPaymentChannel("WEB");
    $request->setPaymentGroup("PRODUCT");
    $request->setCallbackUrl("http://admin.crm.template/test-api");

    $paymentCard = new PaymentCard();
    $paymentCard->setCardHolderName("John Doe");
    $paymentCard->setCardNumber("5528790000000008");
    $paymentCard->setExpireMonth("12");
    $paymentCard->setExpireYear("2030");
    $paymentCard->setCvc("123");
    $paymentCard->setRegisterCard(0);
    $request->setPaymentCard($paymentCard);

    $buyer = new Buyer();
    $buyer->setId("BY789");
    $buyer->setName("John");
    $buyer->setSurname("Doe");
    $buyer->setGsmNumber("+905350000000");
    $buyer->setEmail("email@email.com");
    $buyer->setIdentityNumber("74300864791");
    $buyer->setLastLoginDate("2015-10-05 12:43:35");
    $buyer->setRegistrationDate("2013-04-21 15:12:09");
    $buyer->setRegistrationAddress("Nidakule Göztepe, Merdivenköy Mah. Bora Sok. No:1");
    $buyer->setIp("85.34.78.112");
    $buyer->setCity("Istanbul");
    $buyer->setCountry("Turkey");
    $buyer->setZipCode("34732");
    $request->setBuyer($buyer);

    $shippingAddress = new Address();
    $shippingAddress->setContactName("Jane Doe");
    $shippingAddress->setCity("Istanbul");
    $shippingAddress->setCountry("Turkey");
    $shippingAddress->setAddress("Nidakule Göztepe, Merdivenköy Mah. Bora Sok. No:1");
    $shippingAddress->setZipCode("34742");
    $request->setShippingAddress($shippingAddress);

    $billingAddress = new Address();
    $billingAddress->setContactName("Jane Doe");
    $billingAddress->setCity("Istanbul");
    $billingAddress->setCountry("Turkey");
    $billingAddress->setAddress("Nidakule Göztepe, Merdivenköy Mah. Bora Sok. No:1");
    $billingAddress->setZipCode("34742");
    $request->setBillingAddress($billingAddress);

    $basketItems = array();
    $firstBasketItem = new BasketItem();
    $firstBasketItem->setId("BI101");
    $firstBasketItem->setName("Binocular");
    $firstBasketItem->setCategory1("Collectibles");
    $firstBasketItem->setCategory2("Accessories");
    $firstBasketItem->setItemType(\Iyzipay\Model\BasketItemType::PHYSICAL);
    $firstBasketItem->setPrice("0.3");
    $basketItems[0] = $firstBasketItem;

    $secondBasketItem = new BasketItem();
    $secondBasketItem->setId("BI102");
    $secondBasketItem->setName("Game code");
    $secondBasketItem->setCategory1("Game");
    $secondBasketItem->setCategory2("Online Game Items");
    $secondBasketItem->setItemType("VIRTUAL");
    $secondBasketItem->setPrice("0.5");
    $basketItems[1] = $secondBasketItem;

    $thirdBasketItem = new BasketItem();
    $thirdBasketItem->setId("BI103");
    $thirdBasketItem->setName("Usb");
    $thirdBasketItem->setCategory1("Electronics");
    $thirdBasketItem->setCategory2("Usb / Cable");
    $thirdBasketItem->setItemType("PHYSICAL");
    $thirdBasketItem->setPrice("0.2");
    $basketItems[2] = $thirdBasketItem;
    $request->setBasketItems($basketItems);

    // return $request;
    // dd($request);
    # make request
    // $threedsInitialize = \Iyzipay\Model\ThreedsInitialize::create($request, new IyzicoConfig($this->apiKey, $this->apiSecret, $this->url));
    // dd($this->generateHeaders($request));
    // dd($this->url, $endpoint, $request->toJsonString(), $this->generateHeaders($request));
    $threedsInit = $this->postReq($this->url, $endpoint, $request->toJsonString(), $this->generateHeaders($request), 'json');

    # print result
    dd($threedsInit);
  }
}
