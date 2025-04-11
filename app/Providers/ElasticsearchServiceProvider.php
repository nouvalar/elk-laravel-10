<?php

namespace App\Providers;

use Elastic\Elasticsearch\ClientBuilder;
use Illuminate\Support\ServiceProvider;

class ElasticsearchServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('elasticsearch', function ($app) {
            $host = env('ELASTICSEARCH_HOST', 'localhost');
            $port = env('ELASTICSEARCH_PORT', 9200);
            $scheme = env('ELASTICSEARCH_SCHEME', 'http');
            $user = env('ELASTICSEARCH_USER', 'elastic');
            $pass = env('ELASTICSEARCH_PASS', 'admin123');

            return ClientBuilder::create()
                ->setHosts(["$scheme://$host:$port"])
                ->setBasicAuthentication($user, $pass)
                ->setSSLVerification(false)  // Jika menggunakan http
                ->build();
        });
    }

    public function provides()
    {
        return ['elasticsearch'];
    }

    public function boot()
    {
        //
    }
}