<?php

namespace Unusualify\Payable\Services;

use Buckaroo\BuckarooClient;

class BuckarooService extends PaymentService
{
    protected $service;
    protected $buckaroo;
    protected $websiteKey;
    protected $secretKey;
    protected $issuer;

    public function __construct($mode = null, $service = 'ideal')
    {

        parent::__construct(
            headers: $this->headers,
        );

        $this->setCredentials();
        $this->service = $service;

        $this->buckaroo = new BuckarooClient($this->websiteKey, $this->secretKey);


    }

    /**
     * setCredentials
     *
     * @return void
     */
    public function setCredentials()
    {
        $this->setConfig();

        $this->mode = $this->config['mode'];
        $tempConfig = $this->config[$this->mode];
        $this->websiteKey = $tempConfig['website_key'];
        $this->secretKey = $tempConfig['secret_key'];
    }

   
    /**
     * hydrateParams
     *
     * @param  mixed $params
     * @return array
     */
    public function hydrateParams(array $params) : array
    {
        // dd($params);
        $params = [
            'returnURL' => route('payable.response').'?payment_service='. $this->service, //Returns to this url aftere payment.
            'issuer' => $params['issuer'], // Selected bank 
            'amountDebit' => $params['paid_price'], // The amount we want to charge
            'invoice' => $params['order_id'], // Each payment must contain a unique invoice number
        ];

        return $params;
    }

    public function hydrateRecordParams(array $params) : array
    {
        return $recordParams = [
            'amount' => $params['paid_price'],
            'email' => $params['user_email'],
            'installment' => $params['installment'],
            'parameters' => json_encode($params),
            'order_id' => $params['order_id'],
            'currency' => $params['currency'],
            'payment_gateway' => $this->serviceName,
        ];
    }
}

