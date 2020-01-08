<?php

namespace Netflex\Support\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static mixed get(string $url, bool $assoc = false)
 * @method static \GuzzleHttp\Client getGuzzleInstance()
 * @method static mixed put(string $url, array $payload = [], bool $assoc = false)
 * @method static mixed post(string $url, array $payload = [], bool $assoc = false)
 * @method static mixed delete(string $url, bool $assoc = false)
 *
 * @see \Netflex\API
 */
class API extends Facade
{
  /**
   * Get the registered name of the component.
   *
   * @return string
   */
  protected static function getFacadeAccessor()
  {
    return 'api';
  }
}
