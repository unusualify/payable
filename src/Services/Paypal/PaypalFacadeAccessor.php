<?php

namespace Unusualify\Payable\Services\Paypal;

use Exception;
use Unusualify\Payable\Services\PaypalService as PaypalClient;

class PaypalFacadeAccessor
{
  /**
   * Paypal API provider object.
   *
   * @var
   */
  public static $provider;

  /**
   * Get specific Paypal API provider object to use.
   *
   * @throws Exception
   *
   * @return \Srmklive\Paypal\Services\Paypal
   */
  public static function getProvider()
  {
    return self::$provider;
  }

  /**
   * Set Paypal API Client to use.
   *
   * @throws \Exception
   *
   * @return \Srmklive\Paypal\Services\Paypal
   */
  public static function setProvider()
  {
    // Set default provider. Defaults to ExpressCheckout
    self::$provider = new PaypalClient();
    self::$provider->getAccessToken();
    // dd('here');
    return self::getProvider();
  }
}
