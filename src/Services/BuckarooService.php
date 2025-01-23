<?php

namespace Unusualify\Payable\Services;

use Buckaroo\BuckarooClient;
use Illuminate\Support\Facades\Redirect;

class BuckarooService extends PaymentService
{
    protected $service;
    protected $buckaroo;
    protected $websiteKey;
    protected $secretKey;

    public function __construct($mode = null, $service = 'ideal')
    {

        parent::__construct(
            headers: $this->headers,
        );

        $this->setCredentials();
        $this->service = $service;

        $this->buckaroo = new BuckarooClient($this->websiteKey, $this->secretKey);

    //   $buckaroo = new BuckarooClient($this->websiteKey, $this->secretKey);

    //   $buckaroo->payment('ideal') // Input the desire payment method.
    //     ->pay([
    //         'returnURL' => 'https://example.com/return', //Returns to this url aftere payment.
    //         'issuer'          => 'ABNANL2A', // Selected bank
    //         'amountDebit'   => 10, // The amount we want to charge
    //         'invoice'       => 'UNIQUE-INVOICE-NO', // Each payment must contain a unique invoice number
    //     ]);
      //   dd(get_class_methods($this->buckaroo));
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
     * pay
     *
     * @param  mixed $params
     * @return void
     */
    public function pay(array $params)
    {
        // dd($params);
        $payment = $this->createRecord(
            $this->hydrateRecordParams($params)
        );
        $params = $this->hydrateParams($params);
        // dd($params);
        // dd($params);
        $params['returnURL'] = $params['returnURL'] . '&payment_id=' . $payment->id;
        $resp = $this->buckaroo->method($this->service)->pay($params);
        if($resp->hasRedirect()){
            //TODO: redirect to ideal $resp
            $redirectUrl = $resp->getRedirectUrl();

            return Redirect::to($redirectUrl);

        }else if ($resp->hasError()){
            // dd($resp->getSomeError());
            return $resp->getSomeError();
        }else{
            return 'Something went wrong please contact with administrator.';
        }
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
            'issuer' => 'ABNANL2A', // Selected bank ??
            'amountDebit' => $params['paid_price'], // The amount we want to charge
            'invoice' => $params['order_id'], // Each payment must contain a unique invoice number
            'custom_fields' => $params['custom_fields']

        ];

        return $params;
    }

    public function hydrateRecordParams(array $params) : array
    {
        return $recordParams = [
            'amount' => $params['price'],
            'email' => $params['user_email'],
            'installment' => $params['installment'],
            'payment_service_id' => $params['payment_service_id'],
            'price_id' => $params['price_id'],
            'parameters' => json_encode($params),
            'order_id' => $params['order_id'],
        ];
    }
}

