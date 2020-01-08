<?php

namespace Netflex\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Cache;

class ClearCache
{
  protected $parameter = '_clearcache';

  /**
   * Handle an incoming request.
   *
   * @param  \Illuminate\Http\Request  $request
   * @param  \Closure  $next
   * @return mixed
   */
  public function handle($request, Closure $next)
  {
    if ($request->exists($this->parameter)) {
      $request->request->remove($this->parameter);
      Cache::clear();
    }

    return $next($request);
  }
}
