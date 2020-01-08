<?php

namespace Netflex\Builder;

use Netflex\Builder\Page;

use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\ServiceProvider;

class BuilderServiceProvider extends ServiceProvider
{
  public function register()
  {
    $loader = AliasLoader::getInstance();
    $loader->alias('Page', Page::class);
  }

  public function boot()
  {
    $this->loadPages();
  }

  private function loadPages()
  {
    $pages = Cache::rememberForever('builder/pages', function () {
      return Page::all();
    });

    foreach ($pages as $page) {
      Cache::rememberForever("builder/pages/{$page->id}", function () use ($page) {
        return new Page($page->jsonSerialize());
      });
    }
  }
}
