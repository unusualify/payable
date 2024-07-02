<?php

namespace Unusualify\Payable;

class Payable {


  public $slug;

  public $service;

  public function __construct(string $slug)
  {
    // dd($slug);
    $this->slug = $slug;
    $serviceName = $this->generateClassPath();
    // dd($serviceName);
    $this->service = new $serviceName();
    // dd($this);
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

  public function pay($params, $priceId)
  {
    // dd($params);
    $this->service->pay($params, $priceId);
  }

  public function cancel($params)
  {
    $this->service->cancel($params);
  }

  public function refund($params){
    $this->service->refund($params);
  }


}