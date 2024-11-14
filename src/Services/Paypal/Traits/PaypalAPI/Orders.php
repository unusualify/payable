<?php

namespace Unusualify\Payable\Services\Paypal\Traits\PaypalAPI;

trait Orders
{
    use Orders\Helpers;

    /**
     * Creates an order.
     *
     * @param array $data
     *
     * @throws \Throwable
     *
     * @return array|\Psr\Http\Message\StreamInterface|string
     *
     * @see https://developer.paypal.com/docs/api/orders/v2/#orders_create
     */
    public function createOrder(array $data)
    {
        $this->apiEndPoint = 'v2/checkout/orders';

        $this->options['request_body'] = $data;
        $this->type = 'json';
        $this->verb = 'post';
        // dd($this->apiEndPoint);
        $resp =  $this->doPaypalRequest();

        $this->createRecord(
            [
                'payment_gateway' => $this->serviceName,
                'order_id' => $resp->id,
                'price' => $data['purchase_units']['amount']['value'],
                'currency_id' => $data->currencyId,
                'email' => $data->email,
                'installment' => $data->installment,
                'parameters' => json_encode($data),
            ]
        );
    }

    /**
     * Shows details for an order.
     *
     * @param string $order_id
     *
     * @throws \Throwable
     *
     * @return array|\Psr\Http\Message\StreamInterface|string
     *
     * @see https://developer.paypal.com/docs/api/orders/v2/#orders_get
     */
    public function showOrderDetails(string $order_id)
    {
        $this->apiEndPoint = "v2/checkout/orders/{$order_id}";

        $this->verb = 'get';

        return $this->doPaypalRequest();
    }

    /**
     * Update order details.
     *
     * @param string $order_id
     * @param array  $data
     *
     * @throws \Throwable
     *
     * @return array|\Psr\Http\Message\StreamInterface|string
     *
     * @see https://developer.paypal.com/docs/api/orders/v2/#orders_patch
     */
    public function updateOrder(string $order_id, array $data)
    {
        $this->apiEndPoint = "v2/checkout/orders/{$order_id}";

        $this->options['json'] = $data;

        $this->verb = 'patch';

        return $this->doPaypalRequest(false);
    }

    /**
     * Confirm the order.
     *
     * @param string $order_id
     * @param array  $data
     *
     * @throws \Throwable
     *
     * @return array|\Psr\Http\Message\StreamInterface|string
     */
    public function confirmOrder(string $order_id, array $data)
    {
        $this->apiEndPoint = "v2/checkout/orders/{$order_id}/confirm-payment-source";

        $this->options['json'] = (object) $data;

        $this->verb = 'post';

        return $this->doPaypalRequest();
    }

    /**
     * Authorizes payment for an order.
     *
     * @param string $order_id
     * @param array  $data
     *
     * @throws \Throwable
     *
     * @return array|\Psr\Http\Message\StreamInterface|string
     *
     * @see https://developer.paypal.com/docs/api/orders/v2/#orders_authorize
     */
    public function authorizePaymentOrder(string $order_id, array $data = [])
    {
        $this->apiEndPoint = "v2/checkout/orders/{$order_id}/authorize";

        $this->options['json'] = (object) $data;

        $this->verb = 'post';

        return $this->doPaypalRequest();
    }

    /**
     * Captures payment for an order.
     *
     * @param string $order_id
     * @param array  $data
     *
     * @throws \Throwable
     *
     * @return array|\Psr\Http\Message\StreamInterface|string
     *
     * @see https://developer.paypal.com/docs/api/orders/v2/#orders_capture
     */
    public function capturePaymentOrder(string $order_id, array $data = [])
    {
        $this->apiEndPoint = "v2/checkout/orders/{$order_id}/capture";

        $this->options['json'] = (object) $data;

        $this->verb = 'post';

        return $this->doPaypalRequest();
    }
}
