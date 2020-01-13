<?php

namespace Netflex\Routing;

use Netflex\Http\Middleware\GroupAuthentication;
use Netflex\Http\Middleware\BindPage;
use Netflex\Http\Middleware\JWT;

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

  public function boot()
  {
    // Inject a middleware group for handling Netflex pages
    $router = $this->app->make('router');

    $router->aliasMiddleware('jwt', JWT::class);
    $router->aliasMiddleware('netflex_page', BindPage::class);
    $router->aliasMiddleware('group_auth', GroupAuthentication::class);

    $router->middlewareGroup('netflex_editor', [
      'web',
      'jwt:' . setting('netflex_api')
    ]);
  }
}
