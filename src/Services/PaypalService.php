<?php

namespace Unusualify\Payable\Services;

use Exception;
use GuzzleHttp\Utils;
use Illuminate\Http\Request;
use RuntimeException;
use Unusualify\Payable\Models\Payment;
use Unusualify\Payable\Services\Paypal\Str;
use Unusualify\Payable\Services\Paypal\Traits\PaypalAPI;
use Unusualify\Payable\Services\Paypal\Traits\PaypalVerifyIPN;
use Unusualify\Priceable\Facades\PriceService;
use Unusualify\Priceable\Models\Price;

class PaypalService extends PaymentService
{
    use Paypal\Traits\PaypalConfig;

    use PaypalVerifyIPN;
    use PaypalAPI;

    protected $options;
    protected $httpBodyParam;
    protected $verb;
    protected $type;
    public $apiEndPoint;
    /**
     * Paypal constructor.
     *
     * @param array $config
     *
     * @throws Exception
     */

    //TODO: Subscription service will be added

    public function __construct(array $config = [])
    {
        // Setting Paypal API Credentials
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

    public function pay(array $params)
    {
        $this->apiEndPoint = 'v2/checkout/orders';

        $validatedParams = $this->validateParams($params);
        if($validatedParams != true){
            return $validatedParams;
        }

        $allParams = $this->hydrateParams($params);

        $this->options['request_body'] = $allParams['request_params'];
        $this->type = 'json';
        $this->verb = 'post';
        // dd($this->doPaypalRequest());
        // dd($allParams);
        $payment = $this->createRecord(
            $allParams['record_params']
        );

        $this->options['request_body']['payment_source']['paypal']['experience_context']['return_url'] = $this->options['request_body']['payment_source']['paypal']['experience_context']['return_url']. '&payment_id='.$payment->id;
        $this->options['request_body']['payment_source']['paypal']['experience_context']['cancel_url'] = $this->options['request_body']['payment_source']['paypal']['experience_context']['cancel_url']. '&payment_id='.$payment->id;

        // dd($this->options['request_body']);
        // dd($this);
        $resp =  json_decode($this->doPayPalRequest());
        // dd($this->doPayPalRequest());
        // dd($resp);
        $allParams['record_params']['order_id'] = $resp->id;
        // dd($resp, $resp->id);
        // dd($data);
        // $currency = Price::find($priceID)->currency;
        // dd($resp);
        // dd(((int)$params['purchase_units'][0]['amount']['value']));
        $redirectionUrl = $resp->links[1]->href;
        if($redirectionUrl)
            print(
            "<script>window.open('" . $redirectionUrl . "', '_self')</script>"
            );
        exit;
        // return $resp;
    }

    public function capturePayment($params, array $data = [])
    {
        $this->apiEndPoint = "v2/checkout/orders/{$params['token']}/capture";

        $this->options['request_body'] = (object) $data;

        $this->verb = 'post';

        $this->type = 'json';
        // dd($order_id);
        // dd($this->doPayPalRequest());
        // dd($this->options['request_body']);
        $resp = json_decode($this->doPayPalRequest());
        // dd($resp);
        $data = [
            'payment_source' => $resp->payment_source,
            'purchase_units' => $resp->purchase_units,
            'payer' => $resp->payer,
            'links' => $resp->links
        ];
        // dd($data);
        $custom_fields = $this->updateRecord(
            $params['payment_id'],
            'COMPLETED',
            $data
        );
        $resp->custom_fields = $custom_fields;
        // dd($resp);
        return $resp;
        // return $this->doPayPalRequest();
    }

    // string $capture_id, string $invoice_id, float $amount, string $note, $priceID
    public function refund(array $params)
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

        if(json_decode($resp)->status == 'COMPLETED')
        {
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

    public function showFromSource($orderId)
    {

        $this->apiEndPoint = "v2/checkout/orders/{$orderId}";
        $this->headers['Content-Type'] = 'application/json';
        $this->verb = 'get';
        $resp = $this->doPaypalRequest();

        return $resp;
    }

    public function hydrateParams(array $params)
    {

        $recordParams = [
            'amount' => $params['paid_price'],
            'order_id' => $params['order_id'],
            'email' => $params['user_email'],
            'installment' => $params['installment'],
            'payment_service_id' => $params['payment_service_id'],
            'price_id' => $params['price_id'],
            'parameters' => json_encode($params),
        ];

        $params = [
            'intent' => 'CAPTURE',
            'purchase_units' => [
                [
                    'amount' => [
                        'currency_code' => $params['currency']->iso_4217,
                        'value' => $this->formatAmount($params['paid_price'])
                    ],
                ],
            ],
            'payment_source' => [
                'paypal' => [
                    "name" => [
                    "given_name" => $params['user_name'],
                    'surname' => $params['user_surname'],
                    ],
                    "email_address" => $params['user_email'], //User's email address or empty
                    'experience_context' => [
                        'payment_method_preference' => 'IMMEDIATE_PAYMENT_REQUIRED',
                        'brand_name' => 'B2Press INC',
                        'locale' => 'en-US',
                        'landing_page' => 'LOGIN',
                        'user_action' => 'PAY_NOW',
                        'return_url' => route('payable.response').'?success=true&payment_service=paypal',
                        'cancel_url' => route('payable.response').'?success=false&payment_service=paypal',
                    ],
                ],
            ]
        ];
        return [
        'record_params' => $recordParams,
        'request_params' => $params,
        ];
    }

    public function formatAmount($amount)
    {
        return number_format((float)$amount / 100 , 2, '.', '');
    }

    public function validateParams($params){

        $requiredParams = [
            'order_id',
            'price',
            'currency' ,
            'installment',
            'user_name',
            'user_surname',
            'user_email',
            'payment_service_id',
        ];

        $missingParams = array_diff($requiredParams, array_keys($params));

        if(empty($missingParams)){
            return true;
        }else{
            dd($missingParams ,$params);
            return 'These keys are missing for this payment service' . $missingParams;
        }
    }

    public function handleResponse(Request $request){

        $allParams = $request->query();
        // dd($allParams);
        $params = [];
        // dd($allParams);
        // dd($request);
        if($allParams['success'] == 'true'){
            $resp = $this->capturePayment($allParams);
            // dd($resp);
                // $this->updateRecord(
                //     $allParams['payment_id'],
                //     'COMPLETED',
                //     json_encode($resp)
                // );
                $params = [
                    'status' => 'success',
                    'id' => $allParams['payment_id'],
                    'service_payment_id' => $allParams['token'],
                    'order_id' => $allParams['payment_id'],
                    'payer_id' => $allParams['PayerID'],
                    'custom_fields' => $resp->custom_fields,
                ];
        }else{
            $payment = $this->updateRecord($allParams['payment_id'], 'CANCELLED', json_encode(request()->all()));
            // dd($payment);
            $params = [
                    'status' => 'error',
                    'id' => $allParams['payment_id'],
                    'service_payment_id' => $allParams['token'],
                    'order_id' => $allParams['payment_id'],
                    'payer_id' => isset($allParams['PayerID'] )? $allParams['PayerID'] : '',
                    'custom_fields' => $payment,
            ];
        }
        // dd($params);
        return $this->generatePostForm($params, route(config('payable.return_url')));
        // print_r($this->generatePostForm($params, route(config('payable.return_url'))));
        exit;

    }
}
