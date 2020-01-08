<?php

namespace Netflex\Foundation;

use Netflex\Support\Retrievable;
use Netflex\Support\ReactiveObject;

use \Illuminate\Support\Facades\Cache;

class Setting extends ReactiveObject
{
  use Retrievable;

  protected static $base_path = 'foundation/variables';

  /** @var array */
  protected $timestamps = [];

  /**
   * Retrieve the value of a Setting
   *
   * @param string $key
   * @return mixed
   */
  public static function get($key)
  {
    return Cache::rememberForever("foundation/variables/$key", function () use ($key) {
      return static::retrieve($key);
    })->value;
  }

  /**
   * @param mixed $value
   * @return mixed
   */
  public function getValueAttribute($value)
  {
    switch ($this->format) {
      case 'boolean':
        return (bool) (int) $value;
      case 'json':
        if (is_string($value)) {
          return json_decode($value);
        }

        return $value;
      default:
        return $value;
    }
  }
}
