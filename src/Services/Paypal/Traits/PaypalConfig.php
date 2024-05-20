<?php
namespace Unusualify\Payable\Services\PayPal\Traits;

use RuntimeException;

trait PaypalConfig{

  protected $config;


  //  Modify HERE according to RequestService requirements
  //  We should set api credentials from paypal config to related PaypalService Object
  public function setConfig($config){
    $api_config = empty($config) && function_exists('config') && !empty(config('paypal')) ?
      config('paypal') : $config;
    // Set Api Credentials

    $this->setApiCredentials($api_config);
  }

  public function setRequestHeader(string $key, string $value): \Unusualify\Payable\Services\PayPal\PayPalService{
    $this->options['headers'][$key] = $value;
    return $this;
  }

  public function setApiCredentials(array $credentials): void
    {
        if (empty($credentials)) {
            $this->throwConfigurationException();
        }

        // Setting Default PayPal Mode If not set
        $this->setMode($credentials['mode']);

        // Set API configuration for the PayPal provider
        $this->setApiProviderConfiguration($credentials);

        // Set default currency.
        $this->setCurrency($credentials['currency']);

        // Set Http Client configuration.
        // $this->setHttpClientConfiguration();
    }

  /**
   * Set ExpressCheckout API endpoints & options.
   *
   * @param array $credentials
   */
  public function setOptions(array $credentials): void
  {
    // Setting API Endpoints
    $this->config['api_url'] = 'https://api-m.paypal.com';

    $this->config['gateway_url'] = 'https://www.paypal.com';
    $this->config['ipn_url'] = 'https://ipnpb.paypal.com/cgi-bin/webscr';

    if ($this->mode === 'sandbox') {
      $this->config['api_url'] = 'https://api-m.sandbox.paypal.com';

      $this->config['gateway_url'] = 'https://www.sandbox.paypal.com';
      $this->config['ipn_url'] = 'https://ipnpb.sandbox.paypal.com/cgi-bin/webscr';

    }

    // Adding params outside sandbox / live array
    $this->config['payment_action'] = $credentials['payment_action'];
    $this->config['notify_url'] = $credentials['notify_url'];
    $this->config['locale'] = $credentials['locale'];
  }

  /**
   * Set configuration details for the provider.
   *
   * @param array $credentials
   *
   * @throws \Exception
   */
  private function setApiProviderConfiguration(array $credentials): void
  {
    // Setting PayPal API Credentials
    if (empty($credentials[$this->mode])) {
      $this->throwConfigurationException();
    }

    $config_params = ['client_id', 'client_secret'];

    foreach ($config_params as $item) {
      if (empty($credentials[$this->mode][$item])) {
        throw new RuntimeException("{$item} missing from the provided configuration. Please add your application {$item}.");
      }
    }

    collect($credentials[$this->mode])->map(function ($value, $key) {
      $this->config[$key] = $value;
    });

    $this->paymentAction = $credentials['payment_action'];

    $this->locale = $credentials['locale'];
    $this->setRequestHeader('Accept-Language', $this->locale);

    $this->validateSSL = $credentials['validate_ssl'];

    $this->setOptions($credentials);
  }

  public function setCurrency(string $currency = 'USD'): \Unusualify\Payable\Services\PayPal\PayPalService
  {
    $allowedCurrencies = ['AUD', 'BRL', 'CAD', 'CZK', 'DKK', 'EUR', 'HKD', 'HUF', 'ILS', 'INR', 'JPY', 'MYR', 'MXN', 'NOK', 'NZD', 'PHP', 'PLN', 'GBP', 'SGD', 'SEK', 'CHF', 'TWD', 'THB', 'USD', 'RUB', 'CNY'];

    // Check if provided currency is valid.
    if (!in_array($currency, $allowedCurrencies, true)) {
      throw new RuntimeException('Currency is not supported by PayPal.');
    }

    $this->currency = $currency;

    return $this;
  }

  public function setHttpClientConfiguration(){
    
  }


}