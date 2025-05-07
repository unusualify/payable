<?php

namespace Unusualify\Payable;

use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Session;
use Unusualify\Modularity\Http\Requests\Request;

class Payable {


    public $slug;

    public $service;

    public function __construct(string $slug)
    {
        $this->slug = $slug;
        $serviceName = $this->generateClassPath();
        $this->service = new $serviceName();
        Session::put(config('payable.session_key').'_payment_service', $slug);
    }


    public function generateClassPath()
    {
        return __NAMESPACE__ . '\\Services\\' . $this->toPascalCase($this->slug) . 'Service';
    }

    public function toPascalCase(string $str)
    {
        $arr = array_map('ucfirst', explode('-', $str));
        $str = '';
        for ($i = 0; $i < count($arr); $i++) {
            $str .= ucfirst(strtolower($arr[$i]));
        }
        return $str;
    }

    public function pay($params)
    {
        return $this->service->pay($this->removeExceptional($params));
    }

    public function cancel($params)
    {
        return $this->service->cancel($params);
    }

    public function refund($params)
    {
        return $this->service->refund($params);
    }

    public function formatPrice($price)
    {
        $this->service->formatPrice($price);
    }

    public function removeExceptional($params)
    {
        $exceptionals = config('payable.exceptional_fields.'.$this->slug);
        // dd($exceptionals, 'payable.exceptional_fields.' . $this->slug);
        if($exceptionals)
            foreach($exceptionals as $index => $exception){
                // dd(array_key_exists($exception, $params), $exception, $params);
                if(array_key_exists($exception, $params))
                {
                    // dd($index);
                    unset($params[$exception]);
                }
            }
        // dd($params);
        return $params;
    }

    public function formatAmount($amount)
    {
        return $this->service->formatAmount($amount);
    }

    public function castParams($params)
    {
        $this->service->castParams($params);
    }

    public function handleResponse(HttpRequest $request)
    {
        return $this->service->handleResponse($request);
    }

    public function getPayloadSchema()
    {
        return [
            'locale' => '',
            'payment_service_id' => '',
            'order_id' => '',
            'price' => '',
            'price_id' => '',
            'paid_price' => '',
            'currency' => '',
            'installment' => '',
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
            ]
        ];
    }

}
