<?php

namespace Unusualify\Payable\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Unusualify\Payable\Facades\Payment;
use Unusualify\Payable\Models\Payment as ModelsPayment;

// use Illuminate\Support\Facades\Session;


abstract class PaymentService extends URequest{

    public $mode;

    protected $url;

    protected $root_path;

    protected $token_path;

    protected $path;

    protected $token_refresh_time; //by minute

    protected $redirect_url;

    protected $config;

    public $serviceName;

    protected $headers = [
        'Authorization' => 'Bearer',
        'Content-Type' => 'application/json',
    ];

    public function __construct(
        $headers = null,
        $redirect_url = null)
        {
        parent::__construct(
        mode : $this->mode,
        headers: $this->headers,
        );

        $this->root_path = base_path();
        $this->path = "{$this->root_path}/{$this->token_path}";

        $this->redirect_url = $redirect_url;
        $this->serviceName = str_replace('Service', '', class_basename($this));
    }

    public function getHeaders()
    {
        return $this->headers;
    }

    public function setConfig()
    {
        // dd($this->getConfigName());
        $this->config = config($this->getConfigName());
        // dd($this->config,$this->getConfigName());
        // dd($this->config);
        // dd($this->config, $this->getConfigName());
        $this->mode = $this->config['mode'];
    }

    public function getConfigName()
    {
        // dd('payable' . '.services.' .strtolower(str_replace('Service', '', class_basename($this))));
        return 'payable' . '.services.' .strtolower(str_replace('Service', '', class_basename($this)));
    }

    function createRecord(array $data)
    {
        // dd($data->paymentServiceId);
       	// dd($data);
        $payment = Payment::create(
        $data
        // [
        //   'payment_gateway' => $data['payment_gateway'],
        //   'order_id' => $data['order_id'],
        //   'price' => $data['price'],
        //   // 'currency_id' => isset($data['currency_id']) ? $data['currency_id'] : null,
        //   'email' => $data['email'],
        //   // 'installment' => $data->installment,
        //   'parameters' => json_encode($data),
        //   'payment_service_id' => $data['payment_service_id'],
        //   'price_id' => $data['price_id'],
        // ]
        );
        return $payment;
    }

    static function updateRecord($id, $status, $response)
    {
        // dd($id);
        try{
            $payment = ModelsPayment::findOrFail($id);
            $paymentParams = json_decode($payment->parameters, true);
            $custom_fields = $paymentParams['custom_fields'] ?? null;
            // dd($payment, $paymentParams, $custom_fields, $payment->parameters);
            $updated = $payment->update([
                'status' => $status,
                'response' => $response,
                'parameters' => json_encode($custom_fields),
            ]);
            if($updated){
                return $custom_fields;
            }else{
                return $updated;
            }
        }catch(\Illuminate\Database\Eloquent\ModelNotFoundException $e){
            return $e->getMessage();
        }

    }
    public function setMode($mode)
    {
        $this->mode = $mode;
    }

    abstract function hydrateParams(array $params);

    public function generatePostForm($params, $actionUrl)
    {
        return redirect()
            ->toWithPayload($actionUrl, $params);
        // $form = '<html lang="en">
        //     <head>
        //         <meta charset="UTF-8">
        //         <meta name="viewport" content="width=device-width, initial-scale=1.0">
        //         <title>Payment Confirmation</title>
        //     </head>
        //     <body>';
        // $form .= '<form action="' . htmlspecialchars($actionUrl) . '" method="POST" id="autoSubmitForm">' . "\n";

        // foreach ($params as $key => $value) {
        //     // dd($key, $value);
        //     if(is_array($value)){
        //         // dd($value);
        //         foreach ($value as $subKey => $subValue){
        //             $form .= '<input type="hidden" name="' . htmlspecialchars($key).'['.htmlspecialchars($subKey) . ']' . '" value="' . htmlspecialchars($subValue) . '">' . "\n";
        //         }
        //     }else
        //         $form .= '<input type="hidden" name="' . htmlspecialchars($key) . '" value="' . htmlspecialchars($value) . '">' . "\n";
        // }
        // // dd(csrf_token());
        // $form .= '</form>';
        // $form .= "<script>
        //     window.onload = function() {
        //             document.getElementById('autoSubmitForm').submit();
        //     };
        // </script>";
        // $form .= '</body>
        //     </html>';
        // // dd($form);
        // // dd(session()->all(), csrf_token());
        // return $form;
    }
}
