<?php

namespace Netflex\Http;

use Netflex\Builder\Page;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PageController extends Controller
{
  /**
   * Additional Netflex page routes
   *
   * @var array
   */
  protected $routes = [
    [
      'methods' => ['GET'],
      'action' => 'index',
      'url' => '/'
    ]
  ];

  /**
   * @param Request $request
   * @return Response
   */
  public function index(Request $request)
  {
    return Page::current()
      ->toResponse($request);
  }
}
