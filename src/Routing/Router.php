<?php

namespace Netflex\Routing;

use ArrayObject;
use JsonSerializable;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;

use Psr\Http\Message\ResponseInterface as PsrResponseInterface;

use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;

use Netflex\Routing\Route;
use Netflex\Builder\Structure;
use Netflex\Support\ReactiveObject;
use Netflex\Support\ItemCollection;

class Router extends \Illuminate\Routing\Router
{
  /**
   * Create a new Route object.
   *
   * @param  array|string  $methods
   * @param  string  $uri
   * @param  mixed  $action
   * @return \Netflex\Routing\Route
   */
  protected function newRoute($methods, $uri, $action)
  {
    return (new Route($methods, $uri, $action))
      ->setRouter($this)
      ->setContainer($this->container);
  }

  /**
   * Static version of prepareResponse.
   *
   * @param  \Symfony\Component\HttpFoundation\Request  $request
   * @param  mixed  $response
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public static function toResponse($request, $response)
  {
    if ($response instanceof Responsable) {
      $response = $response->toResponse($request);
    }

    if ($response instanceof PsrResponseInterface) {
      $response = (new HttpFoundationFactory)->createResponse($response);
    } elseif ($response instanceof Structure && $response->wasRecentlyCreated) {
      $response = new JsonResponse($response, 201);
    } elseif (
      !$response instanceof SymfonyResponse &&
      ($response instanceof Arrayable ||
        $response instanceof Jsonable ||
        $response instanceof ArrayObject ||
        $response instanceof JsonSerializable ||
        $response instanceof ReactiveObject ||
        $response instanceof ItemCollection ||
        is_array($response))
    ) {
      $response = new JsonResponse($response);
    } elseif (!$response instanceof SymfonyResponse) {
      $response = new Response($response);
    }

    if ($response->getStatusCode() === Response::HTTP_NOT_MODIFIED) {
      $response->setNotModified();
    }

    return $response->prepare($request);
  }
}
