<?php

namespace Unusualify\Payable\Services;

use Carbon\Carbon;
use Illuminate\Http\Request as HttpRequest;

class TebCommonPosService extends PaymentService
{

    protected $clientId;
    protected $apiUser;
    protected $apiPass;
    public $rnd;
    public $timeSpan;
    protected $params = [];

    public function __construct($mode = null)
    {

        parent::__construct(
        headers: $this->headers,
        );

        $this->setCredentials();

        $this->timeSpan = Carbon::now()->setTimezone('Europe/Istanbul')->format('YmdHis');
        $this->rnd = (string) rand(100000, 999999);

    }

    public function setCredentials()
    {
        // $this->setConfig();

        // $this->mode = $this->config['mode'];
        $tempConfig = $this->config[$this->mode];

        $this->url = $tempConfig['url'];
        $this->clientId = $tempConfig['client_id'];
        $this->apiUser = $tempConfig['api_user'];
        $this->apiPass = $tempConfig['api_password'];

    }

    public function generateHash()
    {
        $hashString = $this->apiPass . $this->clientId . $this->apiUser . $this->rnd . $this->timeSpan;

        $hashingBytes = hash("sha512", ($hashString), true);
        $hash = base64_encode($hashingBytes);

        return $hash;
    }

    public function startPaymentProcess(array $params)
    {
        $endpoint = 'ThreeDPayment';
        $hash = $this->generateHash();
        // dd($params);

        $this->headers = [
            'Content-Type' => 'application/json',
            'Accept' => '*/*'
        ];

        $this->params += $params;

        // $returnUrl = route('payable.response').'?payment_service=teb-common-pos'.'&payment_id='.$payment->id;
        $returnUrl = $this->getRedirectUrl();

        $amount = $this->formatPrice($this->params['amount']);
        $totalAmount = $this->formatPrice($this->params['amount']);
        $data = [
            'clientId' => $this->clientId,
            'apiUser' => $this->apiUser,
            'rnd' => $this->rnd,
            'timeSpan' => $this->timeSpan,
            'hash' => $hash,
            'callbackUrl' => $returnUrl,
            'orderId' => $this->params['order_id'],
            'isCommission' => 0,
            'amount' => $amount,
            'totalAmount' => $totalAmount,
            'currency' =>  Currency::getNumericCode($this->params['currency']),
            'installmentCount' => $this->params['installment'],
            'description' => '',
            'echo' => '',
            'extraParameters' => ''
        ];
        // dd($data, $this->params['currency']);
        $response = $this->postReq($this->url, $endpoint, json_encode($data), $this->headers,'raw');
        $responseObject = json_decode($response);
        // dd($responseObject, $response);
        if ($responseObject->Code == 0) {
            // dd($this->params);
            return $responseObject->ThreeDSessionId;
        }else{
            return json_decode($responseObject);
        }
    }

    public function pay(array $params)
    {
        $threeDSessionId = $this->startPaymentProcess($params);

        $endpoint = 'ProcessCardForm';
        // dd($params);
        $multipart = [
            [
                'name' => 'ThreeDSessionId',
                'contents' => $threeDSessionId,
            ],
            [
                'name' => 'CardHolderName',
                'contents' =>  $this->params['card_name'],
            ],
            [
                'name' => 'CardNo',
                'contents' =>   $this->params['card_no'],
            ],
            [
                'name' => 'ExpireDate',
                'contents' =>   $this->formatCardExpireDate($this->params['card_month'], $this->params['card_year']),
            ],
            [
                'name' => 'Cvv',
                'contents' =>   $this->params['card_cvv'],
            ],
        ];

        $response = $this->postReq($this->url,$endpoint,$multipart,[],'multipart');

        print($response);
    }

    public function formatCardExpireDate($month, $year)
    {
        return $month . substr($year, -2);
    }

    public function hydrateParams(array $params)
    {
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

        $cleanedResponse = array_filter($request->all(), function($key) use ($paramsToRemoved) {
            return !in_array($key, $paramsToRemoved);
        }, ARRAY_FILTER_USE_KEY);

        $responseStatus = ($request->MdStatus == 1 && $request->BankResponseCode == '00' ? self::RESPONSE_STATUS_SUCCESS : self::RESPONSE_STATUS_ERROR);
        $recordStatus = ($request->MdStatus == 1 && $request->BankResponseCode == '00' ? $this->getStatusEnum()::COMPLETED : $this->getStatusEnum()::FAILED);
        $responseMessage = ($request->MdStatus == 1 && $request->BankResponseCode == '00' ? 'Payment successful' : 'Payment failed');

        $this->payment->update([
            'status' => $recordStatus,
            'response' => $cleanedResponse
        ]);

        $responsePayload = [
            'status' => $responseStatus,
            'id' => $request->query('payment_id'),
            'payment_service' => $request->query('payment_service'),
            'order_id' => $request->query('order_id'),
            'order_data' => $request->all(),
            'message' => $responseMessage
        ];

        return $this->generatePostForm($responsePayload, route(config('payable.return_url')));
    }

    public function formatPrice($price)
    {
        return $price;
        return round($price, 2) * 100;
    }
}
