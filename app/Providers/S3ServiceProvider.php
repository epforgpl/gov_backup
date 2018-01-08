<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use S3;

/**
 * Provides a client talking to an S3-compatible service storing files.
 *
 * @package App\Providers
 */
class S3ServiceProvider extends ServiceProvider
{
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
        $this->app->singleton('S3', function ($app) {
            $s3 = new S3();
            $s3->setAuth(getenv('FILES_LOGIN'), getenv('FILES_KEY'));
            $s3->setEndpoint(getenv('FILES_HOST'));
            $s3->setExceptions(true);
            return $s3;
        });
    }
}
