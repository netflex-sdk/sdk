<?php

namespace Netflex\Routing;

use Illuminate\Routing\RoutingServiceProvider as ServiceProvider;

class RoutingServiceProvider extends ServiceProvider
{
  /**
   * Register the router instance.
   *
   * @return void
   */
  protected function registerRouter()
  {
    $this->app->singleton('router', function ($app) {
      return new Router($app['events'], $app);
    });
  }
}
