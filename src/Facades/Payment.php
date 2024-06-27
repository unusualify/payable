<?php

namespace Unusualify\Payable\Facades;

use Illuminate\Support\Facades\Facade;

class Payment extends Facade
{
  protected static function getFacadeAccessor()
  {
    return  \Unusualify\Payable\Models\Payment::class;
  }
}
