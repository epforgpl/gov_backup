<?php

namespace App\Providers;

use App\Storage\iStorage;
use App\Storage\OpenStackStorage;
use App\Storage\S3Storage;
use Elasticsearch\ClientBuilder;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // Provides a client talking to an OpenStack's Object Storage compatible service storing files.
        $this->app->singleton(iStorage::class, function() {
            $storage_type = config('services.storage.type');
            $options = config('services.storage.options');

            if ($storage_type == 'openstack') {
                return new OpenStackStorage($options);

            } else if ($storage_type == 's3') {
                return new S3Storage($options);

            } else {
                throw new \InvalidArgumentException("Unknown storage type: $storage_type");
            }
        });

        $this->app->singleton(\Elasticsearch\Client::class, function ($app) {
            $clientBuilder = ClientBuilder::create()->setHosts([config('services.elastic.endpoint')]);

            return $clientBuilder->build();
        });
    }
}
