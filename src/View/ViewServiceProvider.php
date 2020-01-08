<?php

namespace Netflex\View;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\ViewServiceProvider as ServiceProvider;
use Netflex\Routing\Route;

class ViewServiceProvider extends ServiceProvider
{
  public function boot()
  {
    foreach ([
      'blocks' => __DIR__ . '/directives/blocks.php',
      'content' => __DIR__ . '/directives/content.php',
      'media' => __DIR__ . '/directives/media.php',
      'hasContent' => __DIR__ . '/directives/hasContent.php',
      'elseHasContent' => __DIR__ . '/directives/elseHasContent.php',
      'endHasContent' => __DIR__ . '/directives/endHascontent.php',
      'editorButton' => __DIR__ . '/directives/editorButton.php',
    ] as $name => $handler) {
      $this->loadDirective($name, $handler);
    }

    Blade::if('mode', function ($mode) {
      $route = request()->route();
      if (get_class($route) === Route::class) {
        return ($route->data('mode') ?? 'live') === $mode;
      }

      return $mode === 'live';
    });
  }

  private function loadDirective($name, $handler)
  {
    $directive = Cache::rememberForever($handler, function () use ($handler) {
      return file_get_contents($handler);
    });

    Blade::directive($name, function ($expression) use ($name, $directive) {
      $comment = "<?php // @$name($expression) ?>\n";
      return $comment . str_replace('$expression', $expression, $directive);
    });
  }
}
