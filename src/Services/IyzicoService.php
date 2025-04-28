<?php

namespace Unusualify\Payable\Services;

use Illuminate\Http\Response;
use Unusualify\Payable\Models\Payment;
use Unusualify\Payable\Services\Iyzico\Requests\CreateThreedsPaymentRequest;
use Unusualify\Payable\Services\Iyzico\Models\Address;
use Unusualify\Payable\Services\Iyzico\Models\BasketItem;
use Unusualify\Payable\Services\Iyzico\Models\Buyer;
use Unusualify\Payable\Services\Iyzico\Requests\CreatePaymentRequest;
use Unusualify\Payable\Services\Iyzico\Models\PaymentCard;
use Unusualify\Payable\Services\Iyzico\Models\RequestStringBuilder;
use Unusualify\Payable\Services\Iyzico\Requests\CreateCancelRequest;
use Unusualify\Payable\Services\Iyzico\Requests\CreateRefundRequest;
use Unusualify\Payable\Services\Iyzico\Requests\ReportingPaymentDetailRequest;
use Unusualify\Payable\Services\Iyzico\Requests\Request;
use Illuminate\Http\Request as HttpRequest;


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
        $this->redirect_url = route('payable.response').'?payment_service=iyzico';
        // dd($this->redirect_url);

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
        $validatedParams = $this->validateParams($params);
        if ($validatedParams != true) {
            return "Missing parameter: " . $validatedParams;
        }
        // dd($params);
        // dd($params['user_registration_date']);
        $recordParams = $this->hydrateParams($params);
        // dd($request);
        $payment = $this->createRecord(
            $recordParams
        );
        // dd($payment->id);
        $request = new CreatePaymentRequest();
        $request->setLocale($params['locale']);
        $request->setConversationId($params['order_id']);
        $request->setPrice($this->formatPrice($params['price']));
        $request->setPaidPrice($this->formatPrice($params['paid_price']));
        $request->setCurrency($params['currency']->iso_4217);
        $request->setInstallment($params['installment']);
        $request->setBasketId($params['basket_id']);
        $request->setPaymentChannel("WEB");
        $request->setPaymentGroup($params['payment_group']);
        $request->setCallbackUrl($this->redirect_url . '&payment_id='.$payment->id);

        $paymentCard = new PaymentCard();
        $paymentCard->setCardHolderName($params['card_name']);
        $paymentCard->setCardNumber($params['card_no']);
        $paymentCard->setExpireMonth($params['card_month']);
        $paymentCard->setExpireYear($params['card_year']);
        $paymentCard->setCvc($params['card_cvv']);
        $paymentCard->setRegisterCard(0);
        $request->setPaymentCard($paymentCard);

        $buyer = new Buyer();
        $buyer->setId($params['user_id']);
        $buyer->setName($params['user_name']);
        $buyer->setSurname($params['user_surname']);
        $buyer->setGsmNumber($params['user_gsm']);
        $buyer->setEmail($params['user_email']);
        $buyer->setIdentityNumber($params['user_ip']);
        $buyer->setLastLoginDate($params['user_last_login_date']);
        $buyer->setRegistrationDate($params['user_registration_date']);
        $buyer->setRegistrationAddress($params['user_address']);
        $buyer->setIp($params['user_ip']);
        $buyer->setCity($params['user_city']);
        $buyer->setCountry($params['user_country']);
        $buyer->setZipCode($params['user_zip_code']);
        $request->setBuyer($buyer);

        $shippingAddress = new Address();
        $shippingAddress->setContactName($params['user_name']. ' ' . $params['user_surname']);
        $shippingAddress->setCity($params['user_city']);
        $shippingAddress->setCountry($params['user_country']);
        $shippingAddress->setAddress($params['user_address']);
        $shippingAddress->setZipCode($params['user_zip_code']);
        $request->setShippingAddress($shippingAddress);

        $billingAddress = new Address();
        $billingAddress->setContactName($params['user_name']. ' ' . $params['user_surname']);
        $billingAddress->setCity($params['user_city']);
        $billingAddress->setCountry($params['user_country']);
        $billingAddress->setAddress($params['user_address']);
        $billingAddress->setZipCode($params['user_zip_code']);
        $request->setBillingAddress($billingAddress);

        $basketItems = array();
        foreach ($params['items'] as $item) {
            $basketItem = new BasketItem();
            $basketItem->setId($item['id']);
            $basketItem->setName($item['name']);
            $basketItem->setCategory1($item['category1']);
            $basketItem->setCategory2($item['category2']);
            $basketItem->setItemType($item['type']);
            $basketItem->setPrice($this->formatPrice($item['price']));
            array_push($basketItems, $basketItem);
        }
        $request->setBasketItems($basketItems);

        // dd($request);
        // dd($request->toJsonString(), $this->generateHeaders($request));
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
        // dd($params);
        $request->setPaymentId($params['service_payment_id']);
        $request->setConversationId($params['order_id']);
        $request->setConversationData($params['order_data']);
        //Keep current params,
        $resp = $this->postReq($this->url, $endpoint, $request->toJsonString(), $this->generateHeaders($request), 'raw');
        // dd($resp);
        if((json_decode($resp))->status == 'success'){
            $custom_fields = $this->updateRecord(
                $params['id'],
                'COMPLETED',
                $resp
            );
            if($custom_fields)
                return [
                    'status' => 'success',
                    'custom_fields' => $custom_fields
                ];
            else
                return [
                    'status' => 'success',
                    'custom_fields' => null
                ];
        }else{
            return [
                'status' => 'error',
                'custom_fields' => null
            ];
        }

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
            'email' => $params['user_email'],
            'installment' => $params['installment'],
            'parameters' => json_encode($params),
            'order_id' => $params['order_id'],
            'currency' => $params['currency'],
        ];

        return $recordParams;

    }

    public function validateParams($params, $requiredParams = null)
    {
        if ($requiredParams === null) {
            $requiredParams = [
                'locale',
                'payment_service_id',
                'order_id',
                'price',
                'paid_price',
                'currency',
                'installment',
                'payment_group',
                'card_name',
                'card_no',
                'card_month',
                'card_year',
                'card_cvv',
                'user_id',
                'user_name',
                'user_surname',
                'user_gsm',
                'user_email',
                'user_ip',
                'user_last_login_date',
                'user_registration_date',
                'user_address',
                'user_city',
                'user_country',
                'user_zip_code',
                'items' => [
                    'id',
                    'name',
                    'category1',
                    'category2',
                    'price',
                    'type'
                ]
            ];
        }

        foreach ($requiredParams as $key => $value) {
            if (is_array($value)) {
                // If the value is an array (like 'items'), we need to check each item
                if (!isset($params[$key]) || !is_array($params[$key])) {
                    return $key;
                }
                // Check if all required item fields exist for each item in the payload
                foreach ($params[$key] as $index => $item) {
                    $result = $this->validateParams($item, $value);
                    if ($result !== true) {
                        return $key . '[' . $index . '].' . $result;
                    }
                }
            } else {
                // For non-array values, check if the key exists in the params
                if (!isset($params[$key]) && !isset($params[$value])) {
                    return is_int($key) ? $value : $key;
                }
            }
        }
        return true;
    }

    public function formatPrice($price)
    {
        // Format the number to always show two decimal places
        return number_format($price/100, 1, '.', '');
    }

    public function handleResponse(HttpRequest $request)
    {

        $paramsToRemoved = [
            'card_name',
            'card_no',
            'card_year',
            'card_month',
            'card_cvv',
            'user_ip',
            'oid',
            'orderid',
            'terminaluserid',
            'txnamount',
            'terminalid',
        ];
        // dd($request->all());
        $resp = array_filter($request->all(), function($key) use ($paramsToRemoved) {
            return !in_array($key, $paramsToRemoved);
        }, ARRAY_FILTER_USE_KEY);

        if ($request->status == 'success') {
            $params = [
                'status' => 'success',
                'id' => $request->query('payment_id'),
                'service_payment_id' => $request->paymentId,
                'order_id' => $request->conversationId,
                'order_data' => $request->conversationData
            ];
            // dd('here');
            // Payment::find($id);
            // dd($request);

            $completed = $this->completePayment($params);
            $params['custom_fields']= $completed['custom_fields'];
            // dd('finished');
        }else{
            $params = [
                'status' => 'fail',
                'id' => $request->query('payment_id'),
                'service_payment_id' => $request->paymentId,
                'order_id' => $request->order_id,
                'order_data' => $request->all(),
                'custom_fields' => $resp['custom_fields'],
            ];
        }
        // dd($params);
        return $this->generatePostForm($params, route(config('payable.return_url')));

        // $postRequest = FacadesRequest::create(route(config('payable.return_url')), 'POST', ['body' => $params]);
        // $response = Route::dispatch($postRequest);
        // dd($postRequest);
        // return new Response($response->getContent(), $response->status(), ['Content-Type' => 'text/html']);

        // $this->postResponseToInternalEndpoint($params);
    }
}
