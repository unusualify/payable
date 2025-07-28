<?php

namespace Unusualify\Payable\Services\Paypal\Traits\PaypalAPI\PaymentMethodsTokens;

trait Helpers
{
    /**
     * @var array
     */
    protected $payment_source = [];

    /**
     * @var array
     */
    protected $customer_source = [];

    /**
     * Set payment method token by token id.
     */
    public function setTokenSource(string $id, string $type): \Srmklive\Paypal\Services\Paypal
    {
        $token_source = [
            'id' => $id,
            'type' => $type,
        ];

        return $this->setPaymentSourceDetails('token', $token_source);
    }

    /**
     * Set payment method token customer id.
     */
    public function setCustomerSource(string $id): \Srmklive\Paypal\Services\Paypal
    {
        $this->customer_source = [
            'id' => $id,
        ];

        return $this;
    }

    /**
     * Set payment source data for credit card.
     */
    public function setPaymentSourceCard(array $data): \Srmklive\Paypal\Services\Paypal
    {
        return $this->setPaymentSourceDetails('card', $data);
    }

    /**
     * Set payment source data for Paypal.
     *
     *
     * @return \Srmklive\Paypal\Services\Paypal
     */
    public function setPaymentSourcePaypal(array $data)
    {
        return $this->setPaymentSourceDetails('paypal', $data);
    }

    /**
     * Set payment source data for Venmo.
     */
    public function setPaymentSourceVenmo(array $data): \Srmklive\Paypal\Services\Paypal
    {
        return $this->setPaymentSourceDetails('venmo', $data);
    }

    /**
     * Set payment source details.
     */
    protected function setPaymentSourceDetails(string $source, array $data): \Srmklive\Paypal\Services\Paypal
    {
        $this->payment_source[$source] = $data;

        return $this;
    }

    /**
     * Send request for creating payment method token/source.
     *
     *
     *
     * @return array|\Psr\Http\Message\StreamInterface|string
     *
     * @throws \Throwable
     */
    public function sendPaymentMethodRequest(bool $create_source = false)
    {
        $token_payload = ['payment_source' => $this->payment_source];

        if (! empty($this->customer_source)) {
            $token_payload['customer'] = $this->customer_source;
        }

        return ($create_source === true) ? $this->createPaymentSetupToken(array_filter($token_payload)) : $this->createPaymentSourceToken(array_filter($token_payload));
    }
}
