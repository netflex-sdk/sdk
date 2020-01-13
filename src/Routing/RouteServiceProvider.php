<?php

namespace Netflex\Routing;

use App;
use Throwable;
use Netflex\Builder\Page;
use Netflex\Http\PageController;
use Illuminate\Support\Facades\Log;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
  /**
   * This namespace is applied to your controller routes.
   *
   * In addition, it is set as the URL generator's root namespace.
   *
   * @var string
   */
  protected $namespace = 'App\Http\Controllers';

  /**
   * The path to the "home" route for your application.
   *
   * @var string
   */
  public const HOME = '/';

  /**
   * Define the routes for the application.
   *
   * @return void
   */
  public function map()
  {
    $this->mapPages();
    $this->mapApiRoutes();
    $this->mapWebRoutes();
    $this->mapRedirects();
  }

  /**
   * Registers routes for Pages defined in Netflex
   *
   * @return void
   */
  protected function mapPages()
  {
    Route::middleware('netflex_editor')
      ->group(function () {
        Route::any('_', function (Request $request) {
          $payload = jwt_payload();
          mode($payload->mode);

          switch ($payload->relation) {
            case 'page':
              $page = Page::retrieve($payload->page_id);

              if ($controller = $page->controller) {
                $route = $request->route();
                $route->data('page', $page);
                current_page($page);
                editor_tools($payload->edit_tools ?? null);

                return $controller->index($request);
              }
              break;
          }
        });
      });

    Page::all()
      ->filter(function ($page) {
        return $page->template && $page->template->type === 'page';
      })->each(function ($page) {
        $class = str_replace('\\', '/', $page->template->controller ?? PageController::class);
        $namespace = str_replace('/', '\\', dirname($class));
        $namespace = $namespace === '.' ? $this->namespace : $namespace;
        $class = str_replace('/', '\\', basename($class));

        $controller = "$namespace\\$class";

        tap(new $controller, function ($controller) use ($class, $page, $namespace) {
          $routes = method_exists($controller, 'getRoutes') ? $controller->getRoutes() : collect();
          $routes = collect([
            (object) [
              'methods' => ['GET'],
              'action' => 'index',
              'url' => '/'
            ]
          ])->merge($routes);

          $routes->each(function ($route) use ($class, $page, $namespace) {
            Route::group(['namespace' => $namespace, 'middleware' => ['web', 'netflex_page']], function () use ($route, $class, $page) {
              $methods = $route->methods;
              $route->url = trim($route->url, '/');
              $url = trim("{$page->url}/{$route->url}", '/');
              $action = "$class@{$route->action}";

              $route = Route::middleware('web')
                ->match($methods, $url, $action);

              if (!$page->public) {
                $route->middleware('group_auth');
              }

              /* if ($domain = $page->domain) {
                $route->domain($domain);
                if (strpos($domain, 'www.') !== 0) {
                  $route->domain("www.$domain");
                }
              } */

              $route->data('page', $page);
              $route->name($page->name);
            });
          });
        });
      });
  }

  /**
   * Registers redirects defined in Netflex
   *
   * @return void
   */
  protected function mapRedirects()
  {
    //
  }

  /**
   * Define the "web" routes for the application.
   *
   * These routes all receive session state, CSRF protection, etc.
   *
   * @return void
   */
  protected function mapWebRoutes()
  {
    Route::middleware('web')
      ->namespace($this->namespace)
      ->group(base_path('routes/web.php'));
  }

  /**
   * Define the "api" routes for the application.
   *
   * These routes are typically stateless.
   *
   * @return void
   */
  protected function mapApiRoutes()
  {
    Route::prefix('api')
      ->middleware('api')
      ->namespace($this->namespace)
      ->group(base_path('routes/api.php'));
  }
}
