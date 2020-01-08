<?php

namespace Netflex\Foundation;

use Netflex\Support\Retrievable;
use Netflex\Support\ReactiveObject;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;
use Illuminate\Contracts\Support\Responsable;

class Template extends ReactiveObject implements Responsable
{
  use Retrievable;

  protected static $base_path = 'foundation/templates';

  /** @var array */
  protected $timestamps = [];

  /**
   * @return static|null
   */
  public static function get($id)
  {
    if (is_numeric($id)) {
      return Cache::rememberForever("foundation/templates/$id", function () use ($id) {
        return static::retrieve($id);
      });
    }
  }

  /**
   * @param array $areas
   * @return Collection
   */
  public function getAreasAttribute($areas = [])
  {
    return collect($areas);
  }

  /**
   * Create an HTTP response that represents the object.
   *
   * @param array $variables
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function toResponse($variables = [])
  {
    return View::make("templates/{$this->alias}", $variables)
      ->render();
  }
}
