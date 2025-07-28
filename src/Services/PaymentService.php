<?php

namespace Unusualify\Payable\Services;

use Illuminate\Support\Str;

abstract class PaymentService extends URequest
{
    /**
     * Mode
     *
     * @var string
     */
    public $mode;

    /**
     * URL
     *
     * @var string
     */
    protected $url;

    /**
     * Root Path
     *
     * @var string
     */
    protected $root_path;

    /**
     * Token Path
     *
     * @var string
     */
    protected $token_path;

    /**
     * Path
     *
     * @var string
     */
    protected $path;

    /**
     * Token Refresh Time
     *
     * @var int
     */
    protected $token_refresh_time; // by minute

    /**
     * Redirect URL
     *
     * @var string
     */
    protected $redirect_url;

    /**
     * Payable Return URL
     *
     * @var string
     */
    protected $payableReturnUrl;

    /**
     * Config
     *
     * @var array
     */
    protected $config;

    /**
     * Service Name
     *
     * @var string
     */
    public $name;

    /**
     * Status Enum
     *
     * @var string
     */
    protected $statusEnum;

    /**
     * Payment Model
     *
     * @var \Unusualify\Payable\Models\Payment
     */
    protected $payment;

    /**
     * Has Refund
     *
     * @var bool
     */
    public static $hasRefund = false;

    /**
     * Has Cancel
     *
     * @var bool
     */
    public static $hasCancel = false;

    public const STATUS_PENDING = 'PENDING';

    public const STATUS_COMPLETED = 'COMPLETED';

    public const STATUS_FAILED = 'FAILED';

    public const STATUS_CANCELLED = 'CANCELLED';

    public const STATUS_REFUNDED = 'REFUNDED';

    public const RESPONSE_STATUS_SUCCESS = 'success';

    public const RESPONSE_STATUS_ERROR = 'error';

    /**
     * Http Request Headers
     *
     * @var array
     */
    protected $headers = [
        'Authorization' => 'Bearer',
        'Content-Type' => 'application/json',
    ];

    public function __construct($headers = null, $redirect_url = null)
    {
        parent::__construct(
            mode : $this->mode,
            headers: $this->headers,
        );

        $this->statusEnum = config('payable.status_enum');

        $this->root_path = base_path();

        $this->path = "{$this->root_path}/{$this->token_path}";

        $this->redirect_url = $redirect_url;

        $this->name = str_replace('Service', '', class_basename($this));

        $this->setConfig();

        $this->payableReturnUrl = route(config('payable.return_url'));

        // dd($this->config);
    }

    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Set Config
     *
     * @return void
     */
    public function setConfig()
    {
        $this->config = config($this->getConfigName());

        $this->setMode($this->config['mode']);

        return $this;
    }

    /**
     * Get Config Name
     *
     * @return string
     */
    public function getConfigName()
    {
        $serviceName = Str::kebab(str_replace('Service', '', class_basename($this)));

        return "payable.services.{$serviceName}";
    }

    /**
     * Set Mode
     *
     * @param  string  $mode
     * @return void
     */
    public function setMode($mode)
    {
        $this->mode = $mode;

        return $this;
    }

    public function setRedirectUrl($redirectUrl)
    {
        $this->redirect_url = $redirectUrl;

        return $this;
    }

    public function getRedirectUrl($payload = [])
    {
        return $this->addQueryParameters($this->redirect_url, $payload);
    }

    protected function getPayableReturnUrl($payload = [])
    {
        return $this->addQueryParameters($this->payableReturnUrl, $payload);
    }

    public function getStatusEnum()
    {
        return $this->statusEnum;
    }

    public function addQueryParameters($url, $payload = [])
    {
        $url_parts = parse_url($url);

        $base_url = $url_parts['scheme'].'://'.$url_parts['host'];

        if (isset($url_parts['path'])) {
            $base_url .= $url_parts['path'];
        }

        // Get existing query parameters as array
        $existing_params = [];

        if (isset($url_parts['query'])) {
            parse_str($url_parts['query'], $existing_params);
        }

        // Convert data to array if it's an object
        if (gettype($payload) == 'object') {
            $payload = (array) $payload;
        }

        // Merge existing params with new ones
        $merged_params = array_merge($existing_params, $payload);

        // Construct the new URL
        $query_string = array_to_query_string($merged_params);

        return $base_url.($query_string != '' ? '?'.$query_string : '');
    }

    /**
     * Set Payment
     *
     * @param  \Unusualify\Payable\Models\Payment  $payment
     * @return void
     */
    public function setPayment($payment)
    {
        $this->payment = $payment;

        return $this;
    }

    /**
     * Hydrate Params
     *
     * @return void
     */
    abstract public function hydrateParams(array $params);

    /**
     * Generate Post Form
     *
     * @param  array  $params
     * @param  string  $actionUrl
     * @return void
     */
    public function generatePostForm($params, $actionUrl)
    {
        return redirect()
            ->toWithPayload($actionUrl, $params);
    }

    /**
     * Generate Return Url
     *
     * @return string
     */
    protected function generateReturnUrl(array $parameters)
    {
        return route('payable.response', $parameters);
    }

    /**
     * Create Record
     *
     * @return void
     */
    public function createRecord(array $data)
    {
        $paymentModel = config('payable.model');

        $payment = $paymentModel::create($data);

        return $payment;
    }

    /**
     * Update Record
     *
     * @param  int  $id
     * @param  string  $status
     * @param  string  $response
     * @return void
     */
    public static function updateRecord($id, $status, $response)
    {
        try {

            // if(is_array($response) || is_object($response)){
            //     $response = json_encode($response);
            // }

            $paymentModel = config('payable.model');

            $payment = $paymentModel::findOrFail($id);

            $updated = $payment->update([
                'status' => $status,
                'response' => $response,
            ]);

            return $updated;

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $e->getMessage();
        }

    }

    /**
     * Has Refund
     *
     * @return bool
     */
    public static function hasRefund($payload = null)
    {
        return static::$hasRefund;
    }

    /**
     * Has Cancel
     *
     * @return bool
     */
    public static function hasCancel($payload = null)
    {
        return static::$hasCancel && (method_exists(static::class, 'isCancellable') ? (new static)->isCancellable($payload) : true);
    }

    /**
     * Validate Refund
     *
     * @return bool
     */
    public function validateRefundRequest(array|object $params)
    {
        $type = 'refund';
        $params = (array) $params;

        $refundResponseStatus = $this::RESPONSE_STATUS_ERROR;
        $paymentId = $this->payment ? $this->payment->id : ($params['payment_id'] ?? null);
        $paymentService = $this->payment ? $this->payment->payment_gateway : ($params['payment_service'] ?? $this->service);
        $message = 'Refund failed';
        $validated = false;

        $refundResponse = [
            'type' => $type,
            'id' => $paymentId,
            'status' => $refundResponseStatus,
            'payment_service' => $paymentService,
        ];

        $paymentModel = config('payable.model');

        if (! $paymentId) {
            $message = 'Payment id is required';
        } elseif (($payment = $paymentModel::find($paymentId)) == null) {
            $message = 'Payment not found';
        } elseif ($payment->status != $this->getStatusEnum()::COMPLETED) {
            $message = 'Payment is not completed';
        } else {
            $validated = true;
        }

        return array_merge($refundResponse, [
            'validated' => $validated,
            'payment' => $payment,
            'message' => $message,
        ]);
    }
}
