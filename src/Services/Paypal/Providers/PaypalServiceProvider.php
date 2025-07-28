<?php

namespace Unusualify\Payable\Services\Paypal\Providers;

/*
 * Class PayPalServiceProvider
 * @package Srmklive\Paypal
 */

use Illuminate\Support\ServiceProvider;
use Unusualify\Payable\Services\PaypalService as PaypalClient;

class PaypalServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        // Publish config files
        $this->publishes([
            __DIR__.'/../../config/config.php' => config_path('paypal.php'),
        ]);

        // Publish Lang Files
        $this->loadTranslationsFrom(__DIR__.'/../../lang', 'paypal');
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerPaypal();

        $this->mergeConfig();
    }

    /**
     * Register the application bindings.
     *
     * @return void
     */
    private function registerPaypal()
    {
        $this->app->singleton('paypal_client', static function () {
            return new PaypalClient;
        });
    }

    /**
     * Merges user's and paypal's configs.
     *
     * @return void
     */
    private function mergeConfig()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../../config/config.php',
            'paypal'
        );
    }
}
