<?php

namespace Unusualify\Payable;
use Unusualify\Payable\Providers\RouteServiceProvider;
use Illuminate\Support\ServiceProvider;

class LaravelServiceProvider extends ServiceProvider
{

    protected $providers = [
        RouteServiceProvider::class,
    ];

    /**
     * Boot the application events.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
               __DIR__ . '/../config/config.php' => config_path('payable.php'),
            ], 'config');
        }
        $this->loadMigrationsFrom(
            __DIR__ . '/../src/Database/Migrations'
        );
        // $this->loadViewsFrom(__DIR__ . '/views', 'unusual_form');
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        // $this->bootViews();

    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {

        $this->registerHelpers();

        $this->macros();

        $this->registerProviders();

    }

    /**
     * Register views.
     *
     * @return void
     */
    public function bootViews()
    {
        $sourcePathBlade = __DIR__ .  '/Resources/views';
        $sourcePathJS = __DIR__ .  '/Resources/js';

        // $this->loadViewsFrom( $sourcePathBlade, 'unusual_form');

        $this->publishes([$sourcePathBlade => resource_path('views/vendor/')], 'views');

        $this->publishes([$sourcePathJS => public_path('vendor/payable/js')], 'js');

    }

    /**
     * Register providers.
     */
    protected function registerProviders()
    {
        foreach ($this->providers as $provider) {
            $this->app->register($provider);
        }
    }


    /**
     * {@inheritdoc}
     */
    private function registerHelpers()
    {
        foreach (glob( __DIR__ . '/../Helpers/*.php') as $file) {
            require_once $file;
        }
    }

    /**
     * {@inheritdoc}
     */
    private function macros()
    {

    }

    /**
     * Register facades
     */

    //  protected function registerFacades(){
    //     dd('here');
    //     $this->app->singleton('zoho', function () {
    //         return new Facades\Zoho;
    //     });
    //     dd('here');
    //     $this->app->singleton('movie', function () {
    //         return new Facades\Movie;
    //     });

    //  }

 


}
