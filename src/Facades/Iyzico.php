<?php

namespace Unusualify\Payable\Facades;

use Illuminate\Support\Facades\Facade;

class Iyzico extends Facade
{
  protected static function getFacadeAccessor()
  {
    return  \Unusualify\Payable\Services\Iyzico\IyzicoService::class;
  }
}
