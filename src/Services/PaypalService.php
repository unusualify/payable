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
    protected $service;
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
        $this->service = 'paypal';

        parent::__construct(
            $this->mode,
        );

        $this->getAccessToken();
    }

    public function doPaypalRequest(bool $decode = true)
    {

      
            if($this->verb == 'post'){
                if(!isset($this->options['request_body'])){
                $this->options['request_body'] = [];
                }

                $response = $this->postReq(
                $this->url,
                $this->apiEndPoint,
                $this->options['request_body'],
                $this->headers,
                $this->type,
                $this->mode
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
       
         $payment = $this->createRecord(
            $allParams['record_params']
        ); 
        $this->options['request_body']['payment_source']['paypal']['experience_context']['return_url'] = $this->options['request_body']['payment_source']['paypal']['experience_context']['return_url']. '&payment_id='.$payment->id;
        $this->options['request_body']['payment_source']['paypal']['experience_context']['cancel_url'] = $this->options['request_body']['payment_source']['paypal']['experience_context']['cancel_url']. '&payment_id='.$payment->id;
        

        $response = $this->doPaypalRequest();
          
        if(is_string($response)){
            $response = json_decode($response);
        }

        if (isset($response->id) && $response->id != null && isset($response->status) && $response->status == 'PAYER_ACTION_REQUIRED' && isset($response->links)) {
                $allParams['record_params']['order_id'] = $params['order_id'];
                foreach  ($response->links as $link) {
                    if ($link->rel == 'payer-action') {
                        return redirect()->away($link->href);
                    }
                }

        }
        $params = [
            'status' => $this::RESPONSE_STATUS_ERROR,
            'id' => $payment->id,
            'payment_service' => $this->service,
            'order_id' => $params['order_id'],
            'order_data' => json_encode($response)
        ];

       $response = $this->updateRecord(
            $params['id'],
            self::STATUS_FAILED,
            json_encode($response)
        ); 
        return $this->generatePostForm($params, route(config('payable.return_url')));

    }

    public function capturePayment($params, array $data = [])
    {
        //If authorization will be used
        // $this->apiEndPoint = "v2/checkout/orders/{$params['token']}/authorize";

        $this->apiEndPoint = "v2/checkout/orders/{$params['token']}/capture";

        $this->options['request_body'] = (object) $data;

        $this->verb = 'post';

        $this->type = 'json';
        
        $response = $this->doPaypalRequest();

        if(is_string($response)){
            $response = json_decode($response);
        }
      
        return $response;
    }

    // string $capture_id, string $invoice_id, float $amount, string $note, $priceID
    public function refund(array $params)
    {
        if(empty($params['payment_id'])){
            return [
                'status' => $this::RESPONSE_STATUS_ERROR,
                'id' => $params['payment_id'],
                'payment_service' => $this->service,
                'message' => 'Payment id is required'
            ];
        }
        
        $payment = Payment::find($params['payment_id']);
        if(empty($payment)){
            return [
                'status' => $this::RESPONSE_STATUS_ERROR,
                'id' => $params['payment_id'],
                'payment_service' => $this->service,
                'message' => 'Payment not found'
            ];
        }

        if($payment->status != 'COMPLETED'){
            return [
                'status' => $this::RESPONSE_STATUS_ERROR,
                'id' => $params['payment_id'],
                'payment_service' => $this->service,
                'message' => 'Payment is not completed'
            ];
        }

        if(empty($params['capture_id'])){
            $payment_response = json_decode($payment->response); 
            if (isset($payment_response->purchase_units[0]->payments->captures[0]->id)) {
                $params['capture_id'] = $payment_response->purchase_units[0]->payments->captures[0]->id;
            } else {
                return [
                    'status' => $this::RESPONSE_STATUS_ERROR,
                    'id' => $params['payment_id'],
                    'payment_service' => $this->service,
                    'message' => 'Capture ID not found in payment response'
                ];
            }
        }

        $this->apiEndPoint = "v2/payments/captures/{$params['capture_id']}/refund";
        $this->verb = 'post';
        $this->type = 'raw';

        $this->options['request_body'] = '{}';
        $this->headers['Content-Type'] = 'application/json';

        $response = $this->doPaypalRequest();
        if(is_string($response)){
            $response = json_decode($response);
        }

        if(isset($response->status) && $response->status == "COMPLETED") {
            $this->updateRecord(
                $params['payment_id'],
                self::STATUS_REFUNDED,
                $response
            );
            $return_params = [
                'status' => $this::RESPONSE_STATUS_SUCCESS,
                'id' => $params['payment_id'],
                'payment_service' => $this->service,
                'order_data' => json_encode($response),
                'message' => 'Refunded successfully'
            ];
        } else {
            $error_content = json_decode($response->getContent(), true);
            $return_params = [
                'status' => $this::RESPONSE_STATUS_ERROR,
                'id' => $params['payment_id'],
                'payment_service' => $this->service,
                'order_data' => json_encode($response),
                'message' => isset($error_content['error']) ? $error_content['error'] : 'Refund failed'
            ];
        }
        return $return_params;
    }
    public function cancel(array $params)
    {
        $this->apiEndPoint = "v2/payments/authorizations/{$params['authorization_id']}/void";
        $this->verb = 'post';
        $this->type = 'raw';

    
        $this->options['request_body'] = '{}';
        $this->headers['Content-Type'] = 'application/json';

        $response =  $this->doPaypalRequest();
        if(is_string($response)){
            $response = json_decode($response);
        }

        if(isset($response->status) && $response->status == "VOIDED")
        {
            $this->updateRecord(
                $params['payment_id'],
                self::STATUS_CANCELLED,
                $response
            );
            $return_params = [
                'status' => $this::RESPONSE_STATUS_SUCCESS,
                'id' => $params['payment_id'],
                'payment_service' => $this->service,
                'order_data' => json_encode($response)
            ];
            
        }else{
            $return_params = [
                'status' => $this::RESPONSE_STATUS_ERROR,
                'id' => $params['payment_id'],
                'payment_service' => $this->service,
                'order_data' => json_encode($response)
            ];

        }
        return $return_params;
    }

    public function showFromSource($orderId)
    {

        $this->apiEndPoint = "v2/checkout/orders/{$orderId}";
        $this->headers['Content-Type'] = 'application/json';
        $this->verb = 'get';
        $response = $this->doPaypalRequest();

        return $response;
    }

    public function hydrateParams(array $params)
    {
        $recordParams = [
            'amount' => $params['paid_price'],
            'order_id' => $params['order_id'],
            'email' => $params['user_email'],
            'installment' => $params['installment'],
            'currency' => $params['currency'],
            'parameters' => json_encode($params),
            'payment_gateway' => $this->serviceName,
        ];

        //If authorization will be used
        //'intent' => 'AUTHORIZE',

        $request_params = [
            'intent' => 'CAPTURE',
            'purchase_units' => [
                [
                    'amount' => [
                        'currency_code' => $params['currency'] ?? 'USD',
                        'value' => $this->formatAmount($params['paid_price'])
                    ],
                    'description' => 'Order: ' . $params['order_id'],
                    'custom_id' => $params['order_id'],
                ],
            ],
            'payment_source' => [
                'paypal' => [
                    "name" => [
                        "given_name" => $params['user_name'],
                        'surname' => $params['user_surname'],
                    ],
                    "email_address" => $params['user_email'],
                    'experience_context' => [
                        'payment_method_preference' => 'IMMEDIATE_PAYMENT_REQUIRED',
                        'brand_name' => $params['company_name'],
                        'locale' => $params['locale'] ?? 'en-US',
                        'shipping_preference' => 'NO_SHIPPING',
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
            'request_params' => $request_params,
        ];
    }

    public function formatAmount($amount)
    {
        return number_format((float)$amount , 2, '.', '');
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
            return 'These keys are missing for this payment service' . implode(', ', $missingParams);
        }
    }

    public function handleResponse(Request $request){

        $allParams = $request->query();

        $params = [];
        
        if($allParams['success'] == 'true'){
            $response = $this->capturePayment($allParams);
            if(isset($response->status) && $response->status == "COMPLETED"){
                $this->updateRecord(
                    $allParams['payment_id'],
                    self::STATUS_COMPLETED,
                    json_encode($response)
                );
                $params = [
                    'status' => $this::RESPONSE_STATUS_SUCCESS,
                    'id' => $allParams['payment_id'],
                    'payment_service' => $allParams['payment_service'],
                    'order_id' => $response->purchase_units[0]->payments->captures[0]->custom_id,
                    'payer_id' => isset($allParams['PayerID'] )? $allParams['PayerID'] : '',
                    'token' => $allParams['token'] ?? '',
                    'order_data' => $response
                ];
            }else{
                $this->updateRecord(
                    $allParams['payment_id'],
                    self::STATUS_FAILED,
                    json_encode($response)
                );
                $params = [
                    'status' => $this::RESPONSE_STATUS_ERROR,
                    'id' => $allParams['payment_id'],
                    'payment_service' => $allParams['payment_service'],
                    'payer_id' => isset($allParams['PayerID'] )? $allParams['PayerID'] : '',
                    'token' => $allParams['token'] ?? '',
                    'order_data' => $response
                ];
        
            }
           
        }else{
            $payment = $this->updateRecord($allParams['payment_id'], self::STATUS_FAILED, json_encode(request()->all()));

            $params = [
                    'status' => $this::RESPONSE_STATUS_ERROR,
                    'id' => $allParams['payment_id'],
                    'payment_service' => $allParams['payment_service'],
                    'payer_id' => isset($allParams['PayerID'] )? $allParams['PayerID'] : '',
                    'token' => $allParams['token'] ?? '',
                    'order_data' => request()->all(),
            ];
        }
        return $this->generatePostForm($params, route(config('payable.return_url')));
        exit;

    }
}
