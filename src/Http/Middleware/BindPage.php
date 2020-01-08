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
      if (!app()->has('page')) {

        // Bind the matched page into the container for later use
        // This is used internally in Page::current() to resolve the current page
        app()->bind('page', function () use ($request) {
          return $request->route()->data('page');
        });
      }
    }

    return $next($request);
  }
}
