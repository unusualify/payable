<?php

namespace Unusualify\Payable;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class Payable
{
    /**
     * Service slug
     *
     * @var string
     */
    public $slug;

    /**
     * Service
     */
    public $service;

    /**
     * Payment Model
     *
     * @var string
     */
    private $paymentModel;

    /**
     * Status Enum
     *
     * @var string
     */
    private $statusEnum;

    public function __construct($slug = null)
    {
        $this->paymentModel = config('payable.model');

        $this->statusEnum = config('payable.status_enum');

        if (! $slug) {
            return;
        }

        $this->setService($slug);

        Session::put(config('payable.session_key').'_payment_service', $slug);
    }

    public function setService($slug)
    {
        $this->slug = $slug;

        $serviceClass = $this->getServiceClass($this->slug);

        $this->service = new $serviceClass;

        return $this;
    }

    /**
     * Get Service Class
     *
     * @return string
     */
    public static function getServiceClass($slug)
    {
        $class = __NAMESPACE__.'\\Services\\'.Str::studly($slug).'Service';

        if (! class_exists($class)) {
            throw new \Exception('Service class not found for slug: '.$slug);
        }

        return $class;
        // return __NAMESPACE__ . '\\Services\\' . $this->toPascalCase($this->slug) . 'Service';
    }

    public function pay($payload, $paymentPayload = [])
    {
        $validated = $this->validatePayload($payload);
        $payload = array_merge_recursive_preserve($this->getPayloadSchema(), $payload);

        $payload = $this->removeExceptional($payload);

        $payment = $this->createPaymentRecord($payload, $paymentPayload);

        return $this->service
            ->setRedirectUrl($this->generateReturnUrl([
                'payment_service' => $this->slug,
                'payment_id' => $payment->id,
            ]))
            ->setPayment($payment)
            ->pay($payload, $paymentPayload);
    }

    public function checkout($payload, $paymentPayload = [])
    {
        $validated = $this->validatePayload($payload);

        $payload = array_merge_recursive_preserve($this->getPayloadSchema(), $payload);

        $payload = $this->removeExceptional($payload);

        $payment = $this->createPaymentRecord($payload, $paymentPayload);

        return $this->service
            ->setPayment($payment)
            ->checkout($payload);
    }

    /**
     * Cancel Payment
     *
     * @param  int  $payment_id
     * @param  array|object  $params
     */
    public function cancel($payment_id, $params = [])
    {
        if (! $this->service->hasCancel($params) && method_exists($this->service, 'cancel')) {
            throw new \Exception('Cancel is not supported for the '.$this->service->name);
        }

        $payment = $this->paymentModel::findOrFail($payment_id);

        return $this->service
            ->setRedirectUrl($this->generateReturnUrl([
                'payment_service' => $this->slug,
                'payment_id' => $payment->id,
            ]))
            ->setPayment($payment)
            ->cancel($params);
    }

    public function refund($payment_id, $params = [])
    {
        if (! $this->service->hasRefund($params) && method_exists($this->service, 'refund')) {
            throw new \Exception('Refund is not supported for the '.$this->service->name);
        }

        $payment = $this->paymentModel::findOrFail($payment_id);

        return $this->service
            ->setPayment($payment)
            ->refund($params);
    }

    public function removeExceptional($params)
    {
        $exceptionals = config('payable.exceptional_fields.'.$this->slug);
        // dd($exceptionals, 'payable.exceptional_fields.' . $this->slug);
        if ($exceptionals) {
            foreach ($exceptionals as $index => $exception) {
                // dd(array_key_exists($exception, $params), $exception, $params);
                if (array_key_exists($exception, $params)) {
                    // dd($index);
                    unset($params[$exception]);
                }
            }
        }

        // dd($params);
        return $params;
    }

    public function handleResponse(Request $request)
    {
        if (! $request->get('payment_id')) {
            throw new \Exception('Payment ID is required');
        }

        $payment = $this->paymentModel::find($request->get('payment_id'));

        return $this->service
            ->setPayment($payment)
            ->handleResponse($request);
    }

    protected function validatePayload($payload)
    {
        if (! isset($payload['amount'])) {
            throw new \Exception('Amount is required');
        } elseif (! isset($payload['order_id'])) {
            throw new \Exception('Order ID is required');
        }

        return $payload;
    }

    protected function getPayloadSchema()
    {
        return [
            // 'amount' => null,
            // 'order_id' => '',
            'currency' => 'EUR',

            'locale' => '',
            'installment' => '',

            // 'payment_service_id' => '',
            // 'price' => '',
            // 'price_id' => '',

            'payment_group' => '',

            'card_name' => '',
            'card_no' => '',
            'card_month' => '',
            'card_year' => '',
            'card_cvv' => '',

            'user_id' => '',
            'user_name' => '',
            'user_surname' => '',
            'user_gsm' => '',
            'user_email' => '',
            'user_ip' => '',
            'user_last_login_date' => '',
            'user_registration_date' => '',
            'user_address' => '',
            'user_city' => '',
            'user_country' => '',
            'user_zip_code' => '',

            'items' => [
                'id' => '',
                'name' => '',
                'category1' => '',
                'category2' => '',
                'type' => '',
                'price' => '',
            ],
        ];
    }

    /**
     * Create Payment Record
     *
     * @param  array  $data
     * @return config('payable.model')
     */
    public function createPaymentRecord($payload, $paymentAttributes = [])
    {
        $originalPayload = [
            'amount' => $payload['amount'],
            'currency' => $payload['currency'],
            'email' => $payload['user_email'],
            'installment' => $payload['installment'] ?? 1,
            'order_id' => $payload['order_id'],
            'payment_gateway' => $this->slug,
            'status' => $this->statusEnum::PENDING,
            'parameters' => Arr::except($payload, [
                'user_email',
                'installment',
                'order_id',
                'currency',
                'payment_gateway',
                'amount',
            ]),
        ];

        // If payment id is provided, update the payment
        if (isset($paymentAttributes['id'])) {
            $payment = $this->paymentModel::findOrFail($paymentAttributes['id']);

            if (! in_array($payment->status, [$this->statusEnum::PENDING, $this->statusEnum::FAILED])) {
                throw new \Exception('Payment is not in pending or failed status');
            }

            $payment->update(Arr::except(array_merge($paymentAttributes, $originalPayload), ['id']));

            $payment->refresh();

            return $payment;
        }

        return $this->paymentModel::create(array_merge($paymentAttributes, $originalPayload));
    }

    public function updatePaymentRecord($payment, $status, $response)
    {
        $payment->update([
            'status' => $status,
            'response' => $response,
        ]);
    }

    public function generateReturnUrl($params)
    {
        return route('payable.response', $params);
    }
}
