<?php

namespace Netflex\API;

use Exception;

use Netflex\API;
use Netflex\Support\Facades\API as Facade;

use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\ServiceProvider;

class APIServiceProvider extends ServiceProvider
{
  public function register()
  {
    $this->app->singleton('api', function ($app) {
      return API::getClient();
    });

    $loader = AliasLoader::getInstance();
    $loader->alias('API', Facade::class);
  }

  public function boot()
  {
    $publicKey = $this->app['config']['app.publicKey'];
    $privateKey = $this->app['config']['app.privateKey'];

    if ($publicKey && $privateKey) {
      return API::setCredentials($publicKey, $privateKey);
    }

    throw new Exception('Netflex credentials not configured');
  }
}
