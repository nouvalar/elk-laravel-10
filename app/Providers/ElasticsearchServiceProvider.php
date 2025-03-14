<?php

namespace App\Providers;

use Elastic\Elasticsearch\ClientBuilder;
use Illuminate\Support\ServiceProvider;

class ElasticsearchServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('elasticsearch', function($app) {
            return ClientBuilder::create()
                ->setHosts(['localhost:9200'])
                ->setBasicAuthentication('elastic', 'admin123')
                ->build();
        });
    }

    public function boot()
    {
        //
    }
}