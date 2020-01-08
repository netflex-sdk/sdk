<?php

namespace Netflex\Builder;


use Netflex\Support\Facades\API;
use Netflex\Builder\Page;
use Netflex\Support\ReactiveObject;

use Illuminate\Support\Facades\Cache;

class Content extends ReactiveObject
{
  /** @var array */
  protected $timestamps = [];

  public static function retrieve($id = null, $revision = null)
  {
    $id = $id ?? Page::current()->id;
    $url = trim("builder/pages/$id/content/$revision", '/');
    $areas = Cache::rememberForever($url, function () use ($url) {
      return API::get($url);
    });

    return new static([
      'areas' => $areas
    ]);
  }

  /**
   * @param array $areas
   * @return \Illuminate\Support\Collection
   */
  public function getAreasAttribute($areas = [])
  {
    return collect($areas);
  }
}
