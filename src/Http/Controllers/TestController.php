<?php

namespace Unusualify\Payable\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Unusualify\Payable\Facades\Zoho;
use Unusualify\Payable\Facades\Movie;
use Unusualify\Payable\Facades\Iyzico;
use Unusualify\Payable\Facades\Payment;
use Unusualify\Payable\Facades\PayPal;
use Unusualify\Payable\Services\GarantiPos\GarantiPosService;
use Unusualify\Payable\Services\Iyzico\IyzipayService;
use Unusualify\Payable\Services\TebCommonPos\TebCommonPosService;
use Unusualify\Payable\Services\TebPos\TebPosService;

// use Srmklive\PayPal\PayPalFacadeAccessor as PayPalClient;


class TestController extends Controller
{ 
  public function testView(){
   
  
    /*
    Data for paypal credit / debit card payment
    $data = [
      'intent' => 'CAPTURE',
      "purchase_units"=> [
        [
          "reference_id"=> "Reference_ID_L2L32",
          "description"=> "Description of PU",
          "custom_id"=> "Custom-ID",
          "soft_descriptor"=> "Purchase Descriptor",
          "invoice_id"=> "INV_202302011234",
          "supplementary_data"=> [
            "card"=> [
                "level_2"=> [
                  "invoice_id"=> "INV_202302011234",
                  "tax_total"=> [
                      "currency_code"=> "USD",
                      "value"=> "5.20"
                  ]
                ],
                "level_3"=> [
                  "shipping_amount"=> [
                      "currency_code"=> "USD",
                      "value"=> "1.17"
                  ],
                  "duty_amount"=> [
                      "currency_code"=> "USD",
                      "value"=> "1.16"
                  ],
                  "discount_amount"=> [
                      "currency_code"=> "USD",
                      "value"=> "1.15"
                  ],
                  "shipping_address"=> [
                      "address_line_1"=> "123 Main St.",
                      "admin_area_2"=> "Anytown",
                      "admin_area_1"=> "CA",
                      "postal_code"=> "12345",
                      "country_code"=> "US"
                  ],
                  "ships_from_postal_code"=> "12345",
                  "line_items"=> [
                      [
                        "name"=> "Item1",
                        "description"=> "Description of Item1",
                        "upc"=> [
                            "type"=> "UPC-A",
                            "code"=> "001004697"
                        ],
                        "unit_amount"=> [
                            "currency_code"=> "USD",
                            "value"=> "9.50"
                        ],
                        "tax"=> [
                            "currency_code"=> "USD",
                            "value"=> "5.12"
                        ],
                        "discount_amount"=> [
                            "currency_code"=> "USD",
                            "value"=> "1.11"
                        ],
                        "total_amount"=> [
                            "currency_code"=> "USD",
                            "value"=> "95.10"
                        ],
                        "unit_of_measure"=> "POUND_GB_US",
                        "quantity"=> "10",
                        "commodity_code"=> "98756"
                      ]
                  ]
                ]
            ]
          ],
        ],
      ],
      'payment_source' => [
        'card' => [
          "name" => "Mr. Lorena Lesch",
          "number" => "4033870074426619",
          "security_code" => "492",
          "expiry" => "2033-11",
          "billing_address" => [
            "address_line_1" => "string",
            "address_line_2" => "string",
            "admin_area_2" => "string",
            "admin_area_1" => "string",
            "postal_code" => "string",
            "country_code" => "st"
          ],
          "attributes" => [
            "customer" => [
              "id" => "",
              "email_address" => "test@test.com",
              "phone" => [
                "phone_type" => "FAX",
                "phone-number" => [
                  "national_number" => "+15334044921",
                ]
              ]
            ],
            "vault" => [
              "store_in_vault" => "ON_SUCCESS"
            ],
            "verification" => [
              "method" => "SCA_ALWAYS"
            ]
          ],
          "stored_credential" => [
            "payment_initiator" => "CUSTOMER",
            "payment_type" => "ONE_TIME",
            "usage" => "FIRST",
            // "previous_network_transaction_reference" => [ // This is only compatible when payment_initiator = MERCHANT
            //   "id" => "stringstr",
            //   "date" => "stri",
            //   "acquirer_reference_number" => "string",
            //   "network" => "VISA"
            // ]
          ],
          // "vault_id" => "", // The PayPal-generated ID for the saved card payment source. Typically stored on the merchant's server.
          // "network_token" =>[  //A 3rd party network token refers to a network token that the merchant provisions from and vaults with an external TSP (Token Service Provider) other than PayPal.
          //   "number" => "stringstrings",
          //   "cryptogram" => "stringstringstringstringstri",
          //   "token_requestor_id" => "string",
          //   "expiry" => "string",
          //   "eci_flag" => "MASTERCARD_NON_3D_SECURE_TRANSACTION"
          // ],
          'experience_context' => [
            'return_url' => 'http://admin.crm.template/returnUrl',
            'cancel_url' => 'http://admin.crm.template/cancelUrl',
          ],
        ],
      ],
    ];
    */


  }

  public function paypalResponse(Request $request){
    dd($request->getQueryString());
  }
  
  public function garantiResponse(Request $request){
    dd($request);
  }

  public function tebResponse(Request $request){
    dd($request);
  }

  public function tebCommonResponse(Request $request){
    if($request->BankResponseCode == "00"){
      // dd($request, $request->BankResponseCode);
      TebCommonPosService::updateRecord($request->OrderId, 'COMPLETED' ,$request->all());
      //Update payment model with the response field and remove parameters
      // return view()
      dd('success');
    }else{
      TebCommonPosService::updateRecord($request->OrderId, 'CANCELED', $request->all());
      
    }
    dd($request);
  }

  public function iyzicoResponse(Request $request){
    dd($request);
  }

  public function testIyzico(){

    $priceID = 1;
    $params = [
      "locale" => "tr",
      "orderId" => "123456789",
      "price" => "1.0",
      "paidPrice" => "1.2",
      "installment" => 1,
      "paymentChannel" => "WEB",
      "basketId" => "B67832",
      "paymentGroup" => "PRODUCT",
      "paymentCard" => [
        "cardHolderName" => "John Doe",
        "cardNumber" => "5528790000000008",
        "expireYear" => "2030",
        "expireMonth" => "12",
        "cvc" => "123"
      ],
      "buyer" => [
        "id" => "BY789",
        "name" => "John",
        "surname" => "Doe",
        "identityNumber" => "74300864791",
        "email" => "email@email.com",
        "gsmNumber" => "+905350000000",
        "registrationDate" => "2013-04-21 15:12:09",
        "lastLoginDate" => "2015-10-05 12:43:35",
        "registrationAddress" => "Nidakule Göztepe, Merdivenköy Mah. Bora Sok. No:1",
        "city" => "Istanbul",
        "country" => "Turkey",
        "zipCode" => "34732",
        "ip" => "85.34.78.112"
      ],
      "shippingAddress" => [
        "address" => "Nidakule Göztepe, Merdivenköy Mah. Bora Sok. No:1",
        "zipCode" => "34742",
        "contactName" => "Jane Doe",
        "city" => "Istanbul",
        "country" => "Turkey"
      ],
      "billingAddress" => [
        "address" => "Nidakule Göztepe, Merdivenköy Mah. Bora Sok. No:1",
        "zipCode" => "34742",
        "contactName" => "Jane Doe",
        "city" => "Istanbul",
        "country" => "Turkey"
      ],
      "basketItems" => [
        [
          "id" => "BI101",
          "price" => "0.3",
          "name" => "Binocular",
          "category1" => "Collectibles",
          "category2" => "Accessories",
          "itemType" => "PHYSICAL"
        ],
        [
          "id" => "BI102",
          "price" => "0.5",
          "name" => "Game code",
          "category1" => "Game",
          "category2" => "Online Game Items",
          "itemType" => "VIRTUAL"
        ],
        [
          "id" => "BI103",
          "price" => "0.2",
          "name" => "Usb",
          "category1" => "Electronics",
          "category2" => "Usb / Cable",
          "itemType" => "PHYSICAL"
        ]
      ],
      "currency" => "TRY",
    ];
    $payment = Iyzico::initThreeDS($params, $priceID);

    dd($payment);

  }

  public function testPaypal(){
    $paypal = PayPal::setProvider();
    // Data for paypal wallet payment
    $data = [
        'intent' => 'CAPTURE',
        'purchase_units' => [
            [
                'amount' => [
                    'currency_code' => 'USD',
                    'value' => '100.00'
                ],
            ],
        ],
        'payment_source' => [
            'paypal' => [
                "name" => [
                  "given_name" => 'John',
                  'surname' => 'Doe'
                ],
                "email_address" => 'sb-crmtest@personal.example.com', //User's email address or empty
                'experience_context' => [
                    'payment_method_preference' => 'IMMEDIATE_PAYMENT_REQUIRED',
                    'brand_name' => 'EXAMPLE INC',
                    'locale' => 'en-US',
                    'landing_page' => 'LOGIN',
                    'user_action' => 'PAY_NOW',
                    'return_url' => 'http://admin.crm.template/test-api/paypal-return?success=true',
                    'cancel_url' => 'http://admin.crm.template/test-api/paypal-return?success=false',
                ],
            ],
          ],
      ];
    $response = json_decode($paypal->createOrder($data));
    dd(json_decode($paypal->createOrder($data)));
    $redirectionUrl = json_decode($paypal->createOrder($data))->links[1]->href;

    print(
    "<script>window.open('" . $redirectionUrl . "', '_blank')</script>"
    );
    exit;
    // dd($redirectionUrl);
    
  }

  public function testGaranti(){
    $garanti = new GarantiPosService();
    $params = [
      "cardname" => "Güneş Bizim",
      "cardnumber" => "4155650100416111",
      "cardexpiredatemonth" => "01",
      "cardexpiredateyear" => "2050",
      "cardcvv2" => "715",
      "companyname" => "OLMADIK PROJELER",
      "orderid" => "61f788af7a414",
      "customeremailaddress" => "info@olmadikprojeler.com",
      "customeripaddress" => "172.19.0.1",
      "txnamount" => "100",
      "txncurrencycode" => 949,
      "txninstallmentcount" => "0",
      "lang" => "tr",
      "iscommission" => 0,
      'previous_url' => url()->previous(),
      'email' => 'test@test.com'
    ];
    $resp = $garanti->pay($params);
  }

  public function testTebCommon(){
    $params = [
      "cardname" => "Güneş Bizim",
      "cardnumber" => "4155650100416111",
      "cardexpiredatemonth" => "01",
      "cardexpiredateyear" => "2050",
      "cardcvv2" => "715",
      "companyname" => "OLMADIK PROJELER",
      "orderid" => "61f788af7a414",
      "customeremailaddress" => "info@olmadikprojeler.com",
      "customeripaddress" => "172.19.0.1",
      "txnamount" => "100",
      "txncurrencycode" => 949,
      "txninstallmentcount" => "0",
      "lang" => "tr",
      "iscommission" => 0,
      'previous_url' => url()->previous(),
      'email' => 'test@test.com'
    ];

    $tebCommon = new TebCommonPosService();
    $resp = $tebCommon->pay($params);
    dd($resp);
  }

  public function testTeb(){
    $params = [
      "cardname" => "Güneş Bizim",
      "cardnumber" => "4155650100416111",
      "cardexpiredatemonth" => "01",
      "cardexpiredateyear" => "2050",
      "cardcvv2" => "715",
      "companyname" => "OLMADIK PROJELER",
      "orderid" => "61f788af7a414",
      "customeremailaddress" => "info@olmadikprojeler.com",
      "customeripaddress" => "172.19.0.1",
      "txnamount" => "100",
      "txncurrencycode" => 949,
      "txninstallmentcount" => "0",
      "lang" => "tr",
      "iscommission" => 0,
      'previous_url' => url()->previous(),
      'email' => 'test@test.com'
    ];

    $teb = new TebPosService();
    $teb->pay($params);
  }
}
