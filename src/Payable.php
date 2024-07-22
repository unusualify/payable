<?php

namespace Unusualify\Payable;

class Payable {


  public $slug;

  public $service;

  public function __construct(string $slug)
  {
    $this->slug = $slug;
    $serviceName = $this->generateClassPath();
    $this->service = new $serviceName();
  }


  public function generateClassPath()
  {
    return __NAMESPACE__ . '\\Services\\' . $this->toPascalCase($this->slug) . 'Service';
  }

  public function toPascalCase(string $str)
  {
    $arr = array_map('ucfirst', explode('_', $str));
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
    $this->service->cancel($params);
  }

  public function refund($params)
  {
    $this->service->refund($params);
  }

  public function formatPrice($price)
  {
    $this->service->formatPrice($price);
  }

  public function removeExceptional($params)
  {
    $exceptionals = config('payable.exceptional_fields.'.$this->slug);
    // dd($exceptionals, 'payable.exceptional_fields.' . $this->slug);
    foreach($exceptionals as $index => $exception)
    {
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


}