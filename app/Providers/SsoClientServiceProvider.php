<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Jumbojett\OpenIDConnectClient;

class SsoClientServiceProvider extends ServiceProvider
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
        $this->app->singleton('Jumbojett\OpenIDConnectClient', function ($app) {
            $server_url = config('auth.sso_server_url');
            $client = new OpenIDConnectClient(
                $server_url, config('auth.sso_client_id'), config('auth.sso_client_secret'));
            $client->providerConfigParam([
                'authorization_endpoint' => $server_url . '/oauth/authorization',
                'token_endpoint' => $server_url . '/oauth/token',
                'userinfo_endpoint' => $server_url . '/oauth/userinfo',
                'jwks_uri' => $server_url . '/oauth/jwks',
                'end_session_endpoint' => $server_url . '/oauth/logout'
            ]);
            $client->setRedirectURL(url('/sso-login'));
            $client->addScope(['openid', 'profile', 'email']);
            return $client;
        });
    }
}
