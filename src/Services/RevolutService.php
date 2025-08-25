<?php

namespace Unusualify\Payable\Services;

use Illuminate\Http\Request as HttpRequest;
use function config;

/**
 * @property \Unusualify\Payable\Models\Payment|null $payment
 */
class RevolutService extends PaymentService
{

    /** Gateway id */
    protected string $service = 'revolut';

    /** Config */
    protected ?string $publicKey  = null; // (Optional) not needed for Card Field / Pop-up
    protected ?string $secretKey  = null; // Secret/private key (required)
    protected ?string $apiVersion = null; // e.g. '2024-05-01'


    public function __construct($mode = null)
    {
        parent::__construct($mode);
        $this->setConfig();
    }

    /**
     * Load config
     *
     */
    public function setConfig()
    {
        $this->config = config($this->getConfigName());    // e.g. 'payable.revolut'
        $this->setMode($this->config['mode'] ?? 'sandbox');

        $modeCfg           = $this->config[$this->mode] ?? [];
        $this->url         = rtrim($modeCfg['api_url'] ?? '', '/');
        $this->secretKey   = $modeCfg['secret_key'] ?? null;
        $this->publicKey   = $modeCfg['public_key'] ?? null;   // not required for this flow
        $this->apiVersion  = $this->config['api_version'] ?? null;

        $this->headers = [
            'Authorization'       => 'Bearer '.$this->secretKey,
            'Revolut-Api-Version' => $this->apiVersion,
            'Content-Type'        => 'application/json',
            'Accept'              => 'application/json',
        ];
        $this->setHeaders($this->headers);

        return $this;
    }

    /** Minimal validation for widget flow */
    public function validateParams($params)
    {
        $required = ['order_id', 'currency'];
        $missing  = array_diff($required, array_keys($params));
        if (!empty($missing)) {
            return 'These keys are missing for Revolut: '.implode(', ', $missing);
        }
        return true;
    }

    /** Convert to minor units */
    protected function normalizeAmountMinor(array $params): int
    {
        if (isset($params['amount']))     return (int) $params['amount']; // minor
        if (isset($params['price']))      return (int) $params['price'];  // minor (your package)
        if (isset($params['paid_price'])) return (int) round(((float) $params['paid_price']) * 100); // major→minor
        return 0;
    }

    /** Build Create Order payload */
    public function hydrateParams(array $params): array
    {
        $amountMinor = $this->normalizeAmountMinor($params);

        return [
            'amount'                    => $amountMinor,
            'currency'                  => $params['currency'] ?? 'EUR',
            'merchant_order_ext_ref'    => $params['order_id'],
            'merchant_customer_ext_ref' => $params['user_id'] ?? null,
            'description'               => $params['description'] ?? ('Payment for order '.$params['order_id']),
            'customer'                  => [
                'email' => $params['user_email'] ?? null,
            ],
            'metadata' => [
                'order_id'   => $params['order_id'],
                'user_email' => $params['user_email'] ?? null,
                'ip'         => $params['user_ip'] ?? null,
            ],
            // Optional: 'capture_mode' => 'automatic' | 'manual'
        ];
    }

    /** POST /orders */
    protected function createOrder(array $payload): array
    {
        $resp = $this->postReq(
            $this->url,
            '/orders',
            $payload,
            $this->headers,
            'json',
            $this->mode
        );

        return is_string($resp) ? (array) json_decode($resp, true) : (array) $resp;
    }

    /** GET /orders/{id} (optional UX or webhook confirm) */
    protected function retrieveOrder(string $orderId): array
    {
        $resp = $this->getReq(
            $this->url,
            '/orders/'.urlencode($orderId),
            [],
            $this->headers
        );
        return is_string($resp) ? (array) json_decode($resp, true) : (array) $resp;
    }

    /**
     * Main entry for Card Field / Card Pop-up.
     * Creates the order and returns data your JS embed will use.
     */
    public function createWidgetOrder(array $params): array
    {
        $validated = $this->validateParams($params);
        if ($validated !== true) return ['error' => $validated];

        $payload = $this->hydrateParams($params);
        $order   = $this->createOrder($payload);
        
        // Persist PENDING with raw provider response
        $this->payment?->update([
            'status'   => $this->getStatusEnum()::PENDING,
            'response' => $order,
        ]);

        return [
            'token'     => $order['token'] ?? null,           // REQUIRED by RevolutCheckout()
            'order_id'  => $order['id'] ?? null,
            'env'       => $this->mode === 'production' ? 'production' : 'sandbox',
            'amount'    => $payload['amount'] ?? null,
            'currency'  => $payload['currency'] ?? null,
            'email'     => $payload['customer']['email'] ?? null,
        ];
    }

    /**
     * Optional: keep pay() compatible — just return directive for widget flow.
     * Your controller/UI should call createWidgetOrder() and then run the JS.
     */
    public function pay(array $params)
    {
        $data = $this->createWidgetOrder($params);
        if (!empty($data['token'])) {
            return [
                'type'      => 'widget',
                'order_id'  => $data['order_id'],
                'token'     => $data['token'],
                'env'       => $data['env'],
                'message'   => 'Use Card Field or Pop-up with RevolutCheckout(token, env).',
            ];
        }
        return ['type' => 'error', 'message' => $data['error'] ?? 'Unable to initialize Revolut order'];
    }

    /** Return URL — UX only; don’t finalize here */
    public function handleResponse(HttpRequest $request)
    {
        $q = $request->query();

        $this->payment?->update([
            'status'   => $this->getStatusEnum()::PENDING,
            'response' => $q,
        ]);

        $orderId = $q['order_id'] ?? null;
        $order   = $orderId ? $this->retrieveOrder($orderId) : null;

        $payload = [
            'id'              => $q['payment_id'] ?? ($this->payment->id ?? null),
            'status'          => self::RESPONSE_STATUS_SUCCESS,  // means "return handled"
            'payment_service' => $q['payment_service'] ?? $this->service,
            'token'           => $q['token'] ?? '',
            'order_id'        => $order['id'] ?? $orderId,
            'order_data'      => $order ?: $q,
        ];

        return $this->generatePostForm($payload, $this->payableReturnUrl);
    }

    /** Webhook — source of truth (COMPLETED/FAILED/CANCELLED/AUTHORISED) */
    public function handleWebhook(HttpRequest $request)
    {
        $payload = $request->all();

        $orderId = $payload['order']['id']
            ?? $payload['order_id']
            ?? $payload['merchant_order_ext_ref']
            ?? null;

        $rawState = $payload['order']['state']
            ?? $payload['state']
            ?? $payload['status']
            ?? null;

        if (!$orderId) {
            return response()->json(['status' => 'ignored', 'reason' => 'missing order id'], 202);
        }

        // Optional: re-fetch to confirm latest state
        $order      = $this->retrieveOrder($orderId);
        $finalState = strtoupper($order['state'] ?? ($rawState ? strtoupper($rawState) : 'FAILED'));

        $payment = $this->payment ?: $this->findPaymentByOrderId($orderId);
        if ($payment) {
            $payment->update([
                'status'   => $this->mapRevolutStatus($finalState),
                'response' => $payload,
            ]);
        }

        return response()->json(['ok' => true]);
    }

    /** POST /orders/{id}/cancel (void before capture) */
    public function cancel(array $params)
    {
        $orderId = $params['order_id'] ?? $this->payment->order_id ?? null;
        if (!$orderId) return ['status' => 'error', 'message' => 'Order ID is required'];

        $resp = $this->postReq($this->url, '/orders/'.urlencode($orderId).'/cancel', (object)[], $this->headers, 'json', $this->mode);
        $data = is_string($resp) ? (array) json_decode($resp, true) : (array) $resp;

        $status = self::RESPONSE_STATUS_ERROR;
        if (!isset($data['error'])) {
            $this->payment?->update([
                'status'   => $this->getStatusEnum()::CANCELLED,
                'response' => $data,
            ]);
            $status = self::RESPONSE_STATUS_SUCCESS;
        }

        return [
            'type'            => 'cancel',
            'status'          => $status,
            'id'              => $this->payment->id ?? ($params['payment_id'] ?? null),
            'payment_service' => $this->service,
            'order_data'      => json_encode($data),
        ];
    }

    /** POST /orders/{id}/refund (full/partial; amount in minor units) */
    public function refund(array|object $params)
    {
        $refundRequest = $this->validateRefundRequest($params);
        if (!$refundRequest['validated']) return $refundRequest;

        $params  = (array) $params;
        $payment = $refundRequest['payment'] ?? null;
        $orderId = $params['order_id'] ?? ($payment->order_id ?? null);

        if (!$orderId) {
            return array_merge($refundRequest, ['message' => 'order_id is required for refund']);
        }

        $payload = [];
        if (!empty($params['amount'])) $payload['amount'] = (int) $params['amount']; // minor

        $resp = $this->postReq($this->url, '/orders/'.urlencode($orderId).'/refund', $payload, $this->headers, 'json', $this->mode);
        $data = is_string($resp) ? (array) json_decode($resp, true) : (array) $resp;

        $status  = self::RESPONSE_STATUS_ERROR;
        $message = 'Refund failed';

        if (!isset($data['error'])) {
            $payment?->update([
                'status'   => $this->getStatusEnum()::REFUNDED,
                'response' => $data,
            ]);
            $status  = self::RESPONSE_STATUS_SUCCESS;
            $message = 'Refunded successfully';
        }

        return array_merge($refundRequest, [
            'status'     => $status,
            'order_data' => json_encode($data),
            'message'    => $message,
        ]);
    }

    /** Map Revolut states to your enum */
    protected function mapRevolutStatus(string $revolutStatus): string
    {
        $statusMap = [
            'PENDING'    => $this->getStatusEnum()::PENDING,
            'AUTHORISED' => $this->getStatusEnum()::PENDING,   // wait for capture
            'COMPLETED'  => $this->getStatusEnum()::COMPLETED,
            'FAILED'     => $this->getStatusEnum()::FAILED,
            'CANCELLED'  => $this->getStatusEnum()::CANCELLED,
        ];
        return $statusMap[$revolutStatus] ?? $this->getStatusEnum()::FAILED;
    }

    /** Helper: find Payment by order id for this gateway */
    protected function findPaymentByOrderId($orderId)
    {
        if (!$orderId) return null;
        $paymentModel = config('payable.model');
        if (!$paymentModel) return null;

        return $paymentModel::where('order_id', $orderId)
            ->where('payment_gateway', $this->service)
            ->latest('id')
            ->first();
    }

    /** Keep parent implementation */
    public function generatePostForm($params, $actionUrl = null)
    {
        return parent::generatePostForm($params, $actionUrl);
    }
}