<?php

namespace Netflex\Http\Middleware;

use Closure;

use Netflex\Routing\Route;

class BindPage
{
  /**
   * Handle an incoming request.
   *
   * @param  \Illuminate\Http\Request  $request
   * @param  \Closure  $next
   * @return mixed
   */
  public function handle($request, Closure $next)
  {
    if (get_class($request->route()) === Route::class) {
      current_page($request->route()->data('page'));
    }

    return $next($request);
  }
}
