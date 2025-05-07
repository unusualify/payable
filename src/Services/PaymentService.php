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

    public const STATUS_PENDING = 'PENDING';
    public const STATUS_COMPLETED = 'COMPLETED';
    public const STATUS_FAILED = 'FAILED';
    public const STATUS_CANCELLED = 'CANCELLED';
    public const STATUS_REFUNDED = 'REFUNDED';

    public const RESPONSE_STATUS_SUCCESS = 'success';
    public const RESPONSE_STATUS_ERROR = 'error';

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
        $this->config = config($this->getConfigName());
       
        $this->mode = $this->config['mode'];
    }

    public function getConfigName()
    {
        // dd('payable' . '.services.' .strtolower(str_replace('Service', '', class_basename($this))));
        return 'payable' . '.services.' .strtolower(str_replace('Service', '', class_basename($this)));
    }

    function createRecord(array $data)
    {
        $payment = Payment::create($data);
        return $payment;
    }

    static function updateRecord($id, $status, $response)
    {
        try{

            if(is_array($response) || is_object($response)){
                $response = json_encode($response);
            }
            $payment = ModelsPayment::findOrFail($id);
           
            $updated = $payment->update([
                'status' => $status,
                'response' => $response
            ]);
            
            return $updated;
            
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
    }
}
