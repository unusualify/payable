<?php

namespace Unusualify\Payable\Facades;

use Illuminate\Support\Facades\Facade;

class Teb extends Facade
{
  protected static function getFacadeAccessor()
  {
    return  \Unusualify\Payable\Services\TebPosService::class;
  }
}
