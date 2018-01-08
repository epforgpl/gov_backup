<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Elasticsearch\ClientBuilder;

class ESServiceProvider extends ServiceProvider
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
        $this->app->singleton('ES', function ($app) {
            dd(123);

            $clientBuilder = ClientBuilder::create();
            return $clientBuilder->build();
            /*
            $client = new \Elastica\Client(array(
                'host' => env('ES_HOST'),
                'port' => env('ES_PORT')));
            $search = new \Elastica\Search($client);
            $search->addIndex(Config::get('constants.es_index'))->addType(Config::get('constants.es_type'));
            return $search;
            */
        });
    }
}
