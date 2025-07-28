<?php

namespace Unusualify\Payable\Services;

use Exception;
use Illuminate\Http\Request;
use Unusualify\Payable\Services\Paypal\Traits\PaypalAPI;
use Unusualify\Payable\Services\Paypal\Traits\PaypalConfig;
use Unusualify\Payable\Services\Paypal\Traits\PaypalVerifyIPN;

class PaypalService extends PaymentService
{
    use PaypalAPI, PaypalConfig, PaypalVerifyIPN;

    /**
     * Has Refund
     *
     * @var bool
     */
    public static $hasRefund = true;

    /**
     * Has Cancel
     *
     * @var bool
     */
    public static $hasCancel = true;

    /**
     * Options
     *
     * @var array
     */
    protected $options;

    /**
     * Http Body Param
     *
     * @var string
     */
    protected $httpBodyParam;

    /**
     * Verb
     *
     * @var string
     */
    protected $verb;

    /**
     * Type
     *
     * @var string
     */
    protected $type;

    /**
     * Api End Point
     *
     * @var string
     */
    protected $apiEndPoint;

    /**
     * Service
     *
     * @var string
     */
    protected $service;

    /**
     * Paypal constructor.
     *
     *
     * @throws Exception
     */

    // TODO: Subscription service will be added

    public function __construct(array $config = [])
    {
        // Setting Paypal API Credentials
        // Manage setConfig functio based on the needs of URequest class
        // parent::__construct();
        $this->setConfig();

        $this->url = $this->config['api_url'];
        $this->httpBodyParam = 'form_params';
        $this->options = [];
        $this->setRequestHeader('Accept', 'application/json');
        $this->service = 'paypal';

        // dd($this);
        parent::__construct(
            $this->mode,
        );

        $this->getAccessToken();
    }

    public function doPaypalRequest(bool $decode = true)
    {
        if ($this->verb == 'post') {
            if (! isset($this->options['request_body'])) {
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
        } else { // Get request
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

        if ($validatedParams != true) {
            return $validatedParams;
        }

        $this->options['request_body'] = $this->hydrateParams($params);
        $this->type = 'json';
        $this->verb = 'post';

        $response = $this->doPaypalRequest();

        if (is_string($response)) {
            $response = json_decode($response);
        }

        if (isset($response->id) && $response->id != null && isset($response->status) && $response->status == 'PAYER_ACTION_REQUIRED' && isset($response->links)) {
            $allParams['record_params']['order_id'] = $params['order_id'];
            foreach ($response->links as $link) {
                if ($link->rel == 'payer-action') {
                    return redirect()->away($link->href);
                }
            }
        }

        $payResponse = [
            'type' => 'pay',
            'status' => $this::RESPONSE_STATUS_ERROR,
            'id' => $this->payment->id,
            'payment_service' => $this->service,
            'order_id' => $params['order_id'],
            'order_data' => json_encode($response),
        ];

        $this->payment->update([
            'status' => $this->getStatusEnum()::FAILED,
            'response' => $response,
        ]);

        return $this->generatePostForm($payResponse, route(config('payable.return_url')));
    }

    public function capturePayment($params, array $data = [])
    {
        // If authorization will be used
        // $this->apiEndPoint = "v2/checkout/orders/{$params['token']}/authorize";

        $this->apiEndPoint = "v2/checkout/orders/{$params['token']}/capture";

        $this->options['request_body'] = (object) $data;

        $this->verb = 'post';

        $this->type = 'json';

        $response = $this->doPaypalRequest();

        if (is_string($response)) {
            $response = json_decode($response);
        }

        return $response;
    }

    // string $capture_id, string $invoice_id, float $amount, string $note, $priceID
    /**
     * Refund Paypal Payment
     *
     * @return array
     */
    public function refund(array|object $params)
    {
        $refundRequest = $this->validateRefundRequest($params);

        if (! $refundRequest['validated']) {
            return $refundRequest;
        }

        $params = (array) $params;

        $captureId = $params['capture_id'] ?? null;
        $payment = $refundRequest['payment'] ?? null;

        if (empty($captureId)) {
            if ($payment && $payment->response->purchase_units[0]->payments->captures[0]->id) {
                $captureId = $payment->response->purchase_units[0]->payments->captures[0]->id;
            } else {
                return array_merge($refundRequest, [
                    'message' => 'Capture ID not found in payment response',
                ]);
            }
        }

        $source = $this->showFromSource($payment->response->id);

        if ($source->status != 'COMPLETED' || $source->intent != 'CAPTURE') {
            throw new \Exception('Payment cannot be refunded');
        }

        $this->apiEndPoint = "v2/payments/captures/{$captureId}/refund";
        $this->verb = 'post';
        $this->type = 'raw';
        $this->options['request_body'] = '{}';
        $this->headers['Content-Type'] = 'application/json';

        $response = $this->doPaypalRequest();

        if (is_string($response)) {
            $response = json_decode($response);
        }

        $refundResponseStatus = $this::RESPONSE_STATUS_ERROR;
        $message = 'Refund failed';

        if (isset($response->status) && $response->status == 'COMPLETED') {
            if ($payment) {
                $payment->update([
                    'status' => $this->getStatusEnum()::REFUNDED,
                    'response' => $response,
                ]);
            }

            $refundResponseStatus = $this::RESPONSE_STATUS_SUCCESS;
            $message = 'Refunded successfully';

        } else {
            $error_content = json_decode($response->getContent(), true);

            if (isset($error_content['error'])) {
                $message = $error_content['error'];
            }
        }

        return array_merge($refundRequest, [
            'status' => $refundResponseStatus,
            'order_data' => json_encode($response),
            'message' => $message,
        ]);
    }

    /**
     * Cancel Paypal Payment
     *
     * @return array
     */
    public function cancel(array|object $params)
    {
        $type = 'cancel';

        $params = (array) $params;

        $captureId = null;

        if ($this->payment && $this->payment->response) {

            if (isset($this->payment->response->id)) {
                $captureId = $this->payment->response->purchase_units[0]->payments->captures[0]->id;
            } else {
                throw new \Exception('Order ID not found in payment response');
            }

        } else {
            if ($params['authorization_id'] || $params['order_id'] || $params['id']) {
                $captureId = $params['authorization_id'] ?? $params['capture_id'] ?? $params['order_id'] ?? $params['id'];
            } else {
                throw new \Exception('Authorization ID, Order ID or Payment ID is required');
            }
        }

        $source = $this->showFromSource($captureId);

        if ($source->status != 'COMPLETED' || $source->intent != 'AUTHORIZED') {
            throw new \Exception('Payment cannot be cancelled');
        }

        // dd($source);

        // $this->apiEndPoint = "v2/payments/authorizations/{$orderId}/void";
        $this->type = 'raw';

        $this->options['request_body'] = '{}';
        $this->headers['Content-Type'] = 'application/json';

        // foreach($this->payment->response->purchase_units as $purchase_unit){
        //     foreach($purchase_unit->payments->captures as $capture){
        //         if($capture->id){
        //             $this->verb = 'get';
        //             $this->apiEndPoint = "v2/payments/captures/{$capture->id}";
        //             $response =  $this->doPaypalRequest();
        //         }
        //     }
        // }

        $this->verb = 'post';
        $this->apiEndPoint = "v2/payments/authorizations/{$orderId}/void";

        $response = $this->doPaypalRequest();

        dd($response);

        if (is_string($response)) {
            $response = json_decode($response);
        }

        $cancelResponseStatus = $this::RESPONSE_STATUS_ERROR;
        $paymentId = $this->payment ? $this->payment->id : ($params['payment_id'] ?? null);
        $paymentService = $this->payment ? $this->payment->payment_gateway : ($params['payment_service'] ?? $this->service);

        if (isset($response->status) && $response->status == 'VOIDED') {
            $cancelResponseStatus = $this::RESPONSE_STATUS_SUCCESS;
            if ($this->payment) {
                $this->payment->update([
                    'status' => $this->getStatusEnum()::CANCELLED,
                    'response' => $response,
                ]);
            }
        }

        $cancelResponse = [
            'type' => $type,
            'status' => $cancelResponseStatus,
            'id' => $paymentId,
            'payment_service' => $paymentService,
            'order_data' => json_encode($response),
        ];

        return $cancelResponse;
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
        // If authorization will be used
        // 'intent' => 'AUTHORIZE',

        return [
            'intent' => $params['intent'] ?? 'CAPTURE',
            'purchase_units' => $params['purchase_units'] ?? [
                [
                    'amount' => [
                        'currency_code' => $params['currency'] ?? 'USD',
                        'value' => $this->formatAmount($params['amount']),
                    ],
                    'description' => 'Order: '.$params['order_id'],
                    'custom_id' => $params['order_id'],
                ],
            ],
            'payment_source' => [
                'paypal' => [
                    'name' => [
                        'given_name' => $params['user_name'],
                        'surname' => $params['user_surname'],
                    ],
                    'email_address' => $params['user_email'],
                    'experience_context' => [
                        'payment_method_preference' => 'IMMEDIATE_PAYMENT_REQUIRED',
                        'brand_name' => $params['company_name'],
                        'locale' => $params['locale'] ?? 'en-US',
                        'shipping_preference' => 'NO_SHIPPING',
                        'landing_page' => 'LOGIN',
                        'user_action' => 'PAY_NOW',
                        'return_url' => $this->getRedirectUrl(['success' => 'true']),
                        'cancel_url' => $this->getRedirectUrl(['success' => 'false']),
                    ],
                ],
            ],
        ];

    }

    public function formatAmount($amount)
    {
        return number_format((float) $amount / 100, 2, '.', '');
    }

    public function validateParams($params)
    {

        $requiredParams = [
            'order_id',
            'price',
            'currency',
            'installment',
            'user_name',
            'user_surname',
            'user_email',
            'payment_service_id',
        ];

        $missingParams = array_diff($requiredParams, array_keys($params));

        if (empty($missingParams)) {
            return true;
        } else {
            return 'These keys are missing for this payment service'.implode(', ', $missingParams);
        }
    }

    public function handleResponse(Request $request)
    {
        $allParams = $request->query();

        // for payments table record
        $recordStatus = $this->getStatusEnum()::FAILED;
        $recordResponse = '';
        $recordId = $allParams['payment_id'];

        // for redirect payload
        $responseStatus = $this::RESPONSE_STATUS_ERROR;
        $responseId = $allParams['payment_id'];
        $responsePaymentService = $allParams['payment_service'];
        $responseToken = $allParams['token'] ?? '';
        $responsePayerId = isset($allParams['PayerID']) ? $allParams['PayerID'] : '';
        $responseOrderData = [];
        $responseOrderId = '';

        if ($allParams['success'] == 'true') {
            $paypalResponse = $this->capturePayment($allParams);

            $recordResponse = $paypalResponse;

            $responseOrderData = $paypalResponse;

            if (isset($paypalResponse->status) && $paypalResponse->status == 'COMPLETED') {
                $recordStatus = $this->getStatusEnum()::COMPLETED;

                $responseStatus = $this::RESPONSE_STATUS_SUCCESS;
                $responseOrderId = $paypalResponse->purchase_units[0]->payments->captures[0]->custom_id;
            }

        } else {
            $recordResponse = request()->all();

            $responseOrderData = json_encode(request()->all());
        }

        $this->payment->update([
            'status' => $recordStatus,
            'response' => $recordResponse,
        ]);

        $responsePayload = [
            'id' => $responseId,
            'status' => $responseStatus,
            'payment_service' => $responsePaymentService,
            'token' => $responseToken,
            'payer_id' => $responsePayerId,
            'order_id' => $responseOrderId,
            'order_data' => $responseOrderData,
        ];

        return $this->generatePostForm($responsePayload, route(config('payable.return_url')));
    }

    public function isCancellable($payload)
    {
        if (! $payload) {
            return false;
        }

        $payload = (object) $payload;

        if (! isset($payload->id)) {
            throw new \Exception('Payment id is required');
        }

        if (! isset($payload->status)) {
            throw new \Exception('Payment status is required');
        }

        $orderId = $payload->id;

        $source = $this->showFromSource($orderId);

        return $source->status == 'COMPLETED' && $source->intent == 'AUTHORIZED';
    }

    public function isRefundable($payload)
    {
        if (! $payload) {
            return false;
        }

        $payload = (object) $payload;

        if (! isset($payload->id)) {
            throw new \Exception('Payment id is required');
        }

        if (! isset($payload->status)) {
            throw new \Exception('Payment status is required');
        }

        $orderId = $payload->id;

        $source = $this->showFromSource($orderId);

        return $source->status == 'COMPLETED' && $source->intent == 'CAPTURED';
    }
}
