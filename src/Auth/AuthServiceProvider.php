<?php

namespace Netflex\Auth;

use Illuminate\Support\Facades\Auth;
use Netflex\Auth\NetflexUserProvider;
use Illuminate\Auth\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
  /**
   * Register any application authentication / authorization services.
   *
   * @return void
   */
  public function boot()
  {
    Auth::provider('netflex', function ($app, array $config) {
      return new NetflexUserProvider();
    });
  }
}
