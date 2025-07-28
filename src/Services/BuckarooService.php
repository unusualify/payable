<?php

namespace Unusualify\Payable\Services;

use Buckaroo\BuckarooClient;
use Illuminate\Support\Arr;

class BuckarooService extends PaymentService
{
    /**
     * Service type (e.g., 'ideal')
     *
     * @var string
     */
    protected $service;

    /**
     * Buckaroo client instance
     *
     * @var \Buckaroo\BuckarooClient
     */
    protected $buckaroo;

    /**
     * Website key for Buckaroo
     *
     * @var string
     */
    protected $websiteKey;

    /**
     * Secret key for Buckaroo
     *
     * @var string
     */
    protected $secretKey;

    /**
     * Issuer for Buckaroo
     *
     * @var string
     */
    protected $issuer;

    /**
     * Constructor for BuckarooService
     *
     * @param  string|null  $mode
     * @param  string|null  $service
     */
    public function __construct($mode = null, $service = 'ideal')
    {
        parent::__construct(
            headers: $this->headers,
        );

        $this->setCredentials();

        $this->service = $service;

        $this->buckaroo = new BuckarooClient($this->websiteKey, $this->secretKey, $this->mode);
    }

    /**
     * setCredentials
     *
     * @return void
     */
    public function setCredentials()
    {
        $this->mode = $this->config['mode'] == 'live' ? 'live' : 'test';

        $tempConfig = $this->config[$this->config['mode']];

        $this->websiteKey = $tempConfig['website_key'];

        $this->secretKey = $tempConfig['secret_key'];
    }

    /**
     * hydrateParams
     */
    public function hydrateParams(array|object $params): array
    {
        $params = (array) $params;
        $payload = Arr::only($params, ['issuer']);

        $payload = array_merge($payload, [
            'returnURL' => $this->getRedirectUrl(['status' => 'success']), // Returns to this url aftere payment.
            'returnURLCancel' => $this->getRedirectUrl(['status' => 'cancel']), // Returns to this url aftere payment if user cancels the payment.
            'returnURLError' => $this->getRedirectUrl(['status' => 'error']), // Returns to this url aftere payment if there is an error.
            'returnURLReject' => $this->getRedirectUrl(['status' => 'reject']), // Returns to this url aftere payment if the payment is rejected.
            'amountDebit' => (float) $params['amount'] / 100, // The amount we want to charge
            'currency' => $params['currency'] ?? 'EUR',
            'invoice' => $params['order_id'], // Each payment must contain a unique invoice number
        ]);

        return $payload;
    }
}
