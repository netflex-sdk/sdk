<?php

namespace Netflex\Foundation;

use Netflex\Foundation\Setting;
use Netflex\Foundation\Template;

use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\ServiceProvider;

class FoundationServiceProvider extends ServiceProvider
{
  public function register()
  {
    $loader = AliasLoader::getInstance();
    $loader->alias('Setting', Setting::class);
    $loader->alias('Template', Template::class);
  }

  public function boot()
  {
    $this->loadVariables();
    $this->loadTemplates();
  }

  private function loadVariables()
  {
    $settings = Cache::rememberForever('foundation/variables', function () {
      return Setting::all();
    });

    foreach ($settings as $setting) {
      Cache::rememberForever("foundation/variables/{$setting->alias}", function () use ($setting) {
        return new Setting($setting->jsonSerialize());
      });
    }
  }

  private function loadTemplates()
  {
    $templates = Cache::rememberForever('foundation/templates', function () {
      return Template::all();
    });

    foreach ($templates as $template) {
      Cache::rememberForever("foundation/templates/{$template->id}", function () use ($template) {
        return new Template($template->jsonSerialize());
      });
    }
  }
}
