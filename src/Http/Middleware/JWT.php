<?php

namespace Netflex\Http\Middleware;

use Closure;
use Netflex\Support\JWT as Token;
use Illuminate\Auth\AuthenticationException;
use Netflex\Routing\Route;

class JWT
{
  /**
   * Handle an incoming request.
   *
   * @param \Illuminate\Http\Request  $request
   * @param \Closure $next
   * @param string $secret
   * @return mixed
   */
  public function handle($request, Closure $next, $secret)
  {
    if ($token = $request->get('token')) {
      $request->offsetUnset('token');
      if ($payload = Token::decodeAndVerify($token, $secret)) {
        container_binding('__jwt_payload__', $payload);

        return $next($request);
      }
    }

    throw new AuthenticationException('Unauthenticated.');
  }
}
