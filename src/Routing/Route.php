<?php

namespace Netflex\Routing;

use Illuminate\Routing\Route as BaseRoute;

class Route extends BaseRoute
{
  /**
   * The array of the bound data.
   *
   * @var array
   */
  protected $data = [];

  public function data(string $key, $value = null)
  {
    if ($value) {
      return $this->setData($key, $value);
    }

    return $this->getData($key);
  }

  public function setData(string $key, $value)
  {
    $this->data[$key] = $value;
    return $this;
  }

  public function getData(string $key)
  {
    return $this->data[$key] ?? null;
  }

  public function removeData(string $key)
  {
    if (array_key_exists($key, $this->data)) {
      unset($this->data[$key]);
    }
  }
}
