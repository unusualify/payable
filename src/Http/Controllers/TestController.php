<?php

namespace Unusualify\Payable\Http\Controllers;

use Illuminate\Routing\Controller;
use Srmklive\PayPal\Facades\PayPal as FacadesPayPal;
use Unusualify\Payable\Facades\Zoho;
use Unusualify\Payable\Facades\Movie;
use Unusualify\Payable\Facades\Iyzico;
use Unusualify\Payable\Facades\PayPal;
use Unusualify\Payable\Services\Iyzico\IyzipayService;

class TestController extends Controller
{ 
  public function test(){
    // $requestString = '{"locale": "tr","binNumber":"542119","conversationId": "123456789"}';
    // $requestString = '{"locale":"tr","binNumber":"542119","conversationId":"123456789"}';


    //$requestString = '{"locale":"tr","conversationId":"123456789","price":"1.0","paidPrice":"1.2","installment":1,"paymentChannel":"WEB","basketId":"B67832","paymentGroup":"PRODUCT","paymentCard":{"cardHolderName":"John Doe","cardNumber":"5528790000000008","expireYear":"2030","expireMonth":"12","cvc":"123"},"buyer":{"id":"BY789","name":"John","surname":"Doe","identityNumber":"74300864791","email":"email@email.com","gsmNumber":"+905350000000","registrationDate":"2013-04-21 15:12:09","lastLoginDate":"2015-10-05 12:43:35","registrationAddress":"Nidakule Göztepe, Merdivenköy Mah. Bora Sok. No:1","city":"Istanbul","country":"Turkey","zipCode":"34732","ip":"85.34.78.112"},"shippingAddress":{"address":"Nidakule Göztepe, Merdivenköy Mah. Bora Sok. No:1","zipCode":"34742","contactName":"Jane Doe","city":"Istanbul","country":"Turkey"},"billingAddress":{"address":"Nidakule Göztepe, Merdivenköy Mah. Bora Sok. No:1","zipCode":"34742","contactName":"Jane Doe","city":"Istanbul","country":"Turkey"},"basketItems":[{"id":"BI101","price":"0.3","name":"Binocular","category1":"Collectibles","category2":"Accessories","itemType":"PHYSICAL"},{"id":"BI102","price":"0.5","name":"Game code","category1":"Game","category2":"Online Game Items","itemType":"VIRTUAL"},{"id":"BI103","price":"0.2","name":"Usb","category1":"Electronics","category2":"Usb / Cable","itemType":"PHYSICAL"}],"currency":"TRY","callbackUrl":"https://www.merchant.com/callback"}';


    // $threedsInit = new IyzipayService();
    // dd($threedsInit->initThreeDS());
    // dd($requestString);
    // dd(Iyzico::initThreeDS(), $threedsInit->initThreeDS());

    $provider = PayPal::setProvider();
    dd($provider->getAccessToken());
    // dd($provider->generateBasicAuthHeaders());
    // $provider = FacadesPayPal::setProvider();
    // dd($provider->getAccessToken());



  }
  
}
