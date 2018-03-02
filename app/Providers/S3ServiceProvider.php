<?php

namespace App\Providers;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use S3;

/**
 * Provides a client talking to an S3-compatible service storing files.
 *
 * @package App\Providers
 */
class S3ServiceProvider extends ServiceProvider
{

    public function __construct(Application $app) {
        parent::__construct($app);
    }

    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        // While S3 is mostly a collection of static vars and methods, the "setEndpoint()" method is not (probably
        // an omission of the developer), so we have to create an instance..
        $this->app->singleton(S3::class, function ($app) {
            $s3 = new S3();
            $s3->setAuth(config('services.s3.key'), config('services.s3.secret'));
            $s3->setEndpoint(config('services.s3.endpoint'));
            $s3->setExceptions(true);

            return $s3;
        });
    }
}
