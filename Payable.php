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
    // dd($this);
  }


  public function generateClassPath(){
    
    return __NAMESPACE__ . '\\' . $this->toPascalCase($this->slug) . 'Service';

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

  public function pay($params){
    $this->service->pay($params);
  }


}