<?php

namespace Dorvidas\JsonApiClient\Providers;

use Dorvidas\JsonApiClient\JsonApiClient;
use Illuminate\Support\ServiceProvider;

class JsonApiClientServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../config/json_api.php' => config_path('json_api.php'),
        ]);
    }

    public function register()
    {
        $this->app->bind(JsonApiClient::class, function ($app) {
            $headers = [
                'base_uri' => config('json_api.url'),
                'http_errors' => false
            ];

            $client = new \GuzzleHttp\Client($headers);

            return new \Dorvidas\JsonApiClient\JsonApiClient(
                $client,
                session('jwt')
            );
        });

        $this->app->alias(JsonApiClient::class, 'jsonApiClient');
    }

}
