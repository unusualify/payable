<?php

namespace Unusualify\Payable\Facades;

/*
 * Class Facade
 * @package Srmklive\Paypal\Facades
 * @see Srmklive\Paypal\ExpressCheckout
 */

use Illuminate\Support\Facades\Facade;

class Paypal extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return \Unusualify\Payable\Services\Paypal\PaypalFacadeAccessor::class;
    }
}
