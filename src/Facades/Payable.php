<?php

namespace Unusualify\Payable\Facades;

use Illuminate\Support\Facades\Facade;

class Payable extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'payable';
    }
}
