<?php

namespace Netflex\View;

use Netflex\View\Compilers\BladeCompiler;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\ViewServiceProvider as ServiceProvider;

class ViewServiceProvider extends ServiceProvider/*  */
{
  public function boot()
  {
    foreach ([
      'blocks' => __DIR__ . '/Directives/blocks.php',
      'content' => __DIR__ . '/Directives/content.php',
      'media' => __DIR__ . '/Directives/media.php',
      'nav' => __DIR__ . '/Directives/nav.php',
      'image' => __DIR__ . '/Directives/image.php',
      'picture' => __DIR__ . '/Directives/picture.php',
      'static' => __DIR__ . '/Directives/static.php',
      'editorButton' => __DIR__ . '/Directives/editorButton.php',
    ] as $name => $handler) {
      $this->loadDirective($name, $handler);
    }

    Blade::if('mode', function (...$modes) {
      return in_array(mode(), $modes);
    });
  }

  private function loadDirective($name, $handler)
  {
    $directive = Cache::rememberForever($handler, function () use ($handler) {
      return file_get_contents($handler);
    });

    Blade::directive($name, function ($expression) use ($name, $directive) {
      return str_replace('$expression', $expression, $directive);
    });
  }

  /**
   * Register the Blade compiler implementation.
   *
   * @return void
   */
  public function registerBladeCompiler()
  {
    $this->app->singleton('blade.compiler', function () {
      return new BladeCompiler(
        $this->app['files'],
        $this->app['config']['view.compiled']
      );
    });
  }
}
