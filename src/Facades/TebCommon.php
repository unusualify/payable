<?php

namespace Unusualify\Payable\Facades;

use Illuminate\Support\Facades\Facade;

class TebCommon extends Facade
{
  protected static function getFacadeAccessor()
  {
    return  \Unusualify\Payable\Services\TebCommonPosService::class;
  }
}
