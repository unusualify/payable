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
               __DIR__ . '/../config/payable.php' => config_path('payable.php'),
            ], 'config');
            $this->publishMigrations();
        }
        // dd(__DIR__.'/../routes/web.php');
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');

        // $this->loadMigrationsFrom(
        //     __DIR__ . '/../src/Database/Migrations'
        // );
        // $this->loadViewsFrom(__DIR__ . '/views', 'unusual_form');
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

        $this->mergeConfigFrom(
            __DIR__.'/../config/payable.php',
            'payable'
        );

        $this->app->scoped('payable', function () {
            return new Payable();
        });
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
     * Publish migration files.
     *
     * @return void
     */
    protected function publishMigrations()
    {
        $timestamp = date('Y_m_d_His');

        $stubPath = __DIR__ . '/../stubs/create_payments_table.stub';
        $targetPath = database_path("migrations/{$timestamp}_create_payments_table.php");

        $this->publishes([
            $stubPath => $targetPath,
        ], 'migrations');
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
