<?php

namespace Netflex\View;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\ViewServiceProvider as ServiceProvider;

class ViewServiceProvider extends ServiceProvider
{
  public function boot()
  {
    foreach ([
      'blocks' => __DIR__ . '/directives/blocks.php',
    ] as $name => $handler) {
      $this->loadDirective($name, $handler);
    }
  }

  private function loadDirective($name, $handler)
  {
    $directive = Cache::rememberForever($handler, function () use ($handler) {
      return file_get_contents($handler);
    });

    Blade::directive($name, function ($expression) use ($directive) {
      return str_replace('$expression', $expression, $directive);
    });
  }
}
