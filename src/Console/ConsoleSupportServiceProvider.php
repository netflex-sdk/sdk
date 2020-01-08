<?php

namespace Netflex\Console;

use Illuminate\Foundation\Providers\ConsoleSupportServiceProvider as ServiceProvider;

class ConsoleSupportServiceProvider extends ServiceProvider
{
  /**
   * The provider class names.
   *
   * @var array
   */
  protected $providers = [
    \Netflex\Console\ArtisanServiceProvider::class,
    \Illuminate\Foundation\Providers\ComposerServiceProvider::class,
    \Laravel\Tinker\TinkerServiceProvider::class,
  ];
}
