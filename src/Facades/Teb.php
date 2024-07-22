<?php

namespace Unusualify\Payable\Facades;

use Illuminate\Support\Facades\Facade;

class Garanti extends Facade
{
  protected static function getFacadeAccessor()
  {
    return  \Unusualify\Payable\Services\TebPosService::class;
  }
}
