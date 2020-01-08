<?php

namespace Netflex\Routing;

use Illuminate\Routing\RoutingServiceProvider as ServiceProvider;
use Netflex\Builder\Page;

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

  public function boot()
  {
    // Inject a middleware group for handling Netflex pages
    $router = $this->app->make('router');

    $router->middlewareGroup('page', [
      \Netflex\Http\Middleware\BindPage::class,
    ]);

    $router->middlewareGroup('group_auth', [
      \Netflex\Http\Middleware\GroupAuthentication::class,
    ]);
  }
}
