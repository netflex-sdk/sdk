<?php

namespace Netflex\Builder;

use Netflex\Foundation\Setting;
use Netflex\Foundation\Template;
use Netflex\Http\PageController;
use Netflex\Support\Retrievable;
use Netflex\Support\ReactiveObject;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;

/**
 * @property int $id
 * @property int $group_id
 * @property string $name
 * @property string $url
 * @property bool $children_inherit_url
 * @property Template|null $template
 * @property Controller|null $controller
 * @property bool $published
 * @property int $revision
 * @property bool use_time
 * @property string|null $start
 * @property string|null $stop
 * @property bool $visible
 * @property bool $visible_nav
 * @property bool $visible_subnav
 * @property bool $nav_hidden_xs
 * @property bool $nav_hidden_sm
 * @property bool $nav_hidden_md
 * @property bool $nav_hidden_lg
 * @property string $nav_target
 * @property Page|null $parent
 * @property int $parent_id
 * @property string $title
 * @property int $sorting
 * @property string $add_to_head
 * @property string $add_to_bodyclose
 * @property string $body_class
 * @property bool $public
 * @property string $author
 *
 */
class Page extends ReactiveObject implements Responsable
{
  use Retrievable;

  protected static $base_path = 'builder/pages';

  /** @var array */
  protected $timestamps = [];

  /**
   * @return static|null
   */
  public static function get($id)
  {
    return Cache::rememberForever("builder/pages/$id", function () use ($id) {
      return static::retrieve($id);
    });
  }

  /**
   * Retrieves the current Page if routes through a Netflex route
   *
   * @return static
   */
  public static function current()
  {
    if (App::has('page')) {
      return App::make('page');
    }
  }

  /**
   * @param string $url
   * @return string
   */
  public function getUrlAttribute($url)
  {
    return $url === 'index/' ? '/' : $url;
  }

  /**
   * @param string $title
   * @return string
   */
  public function getTitleAttribute($title)
  {
    return trim(($title ?? $this->name) . Setting::get('site_meta_title'), ' -');
  }

  /**
   * @return bool
   */
  public function hasTemplate()
  {
    return (bool) ($this->attributes['template'] ?? null)
      && is_numeric($this->attributes['template']);
  }

  /**
   * @param int $template
   * @return Template
   */
  public function getTemplateAttribute($template = null)
  {
    if ($this->hasTemplate()) {
      return Template::get($template);
    }
  }

  /**
   * @param string $controller
   * @return Controller|null
   */
  public function getControllerAttribute($controller = null)
  {
    if ($controller) {
      return App::make($controller);
    }

    if ($this->hasTemplate()) {
      return App::make(PageController::class);
    }
  }

  public function getContentAttribute()
  {
    return Content::retrieve($this->id);
  }

  public function getParentAttribute()
  {
    if ($this->parent_id) {
      return static::retrieve($this->parent_id);
    }
  }

  public function getContentRevision($revision)
  {
    return Content::retrieve($this->id, $revision);
  }

  /**
   * Renders the given blocks
   *
   * @param string $area
   * @param array $vars
   * @return string
   */
  public function getBlocks($area, $vars = [])
  {
    $blocks = [];

    $sections = $this->content->areas->filter(function ($contentArea) use ($area) {
      return $contentArea->area === $area;
    })->values();

    foreach ($sections as $section) {
      $blockhash = $section->title;
      $component = Template::get($section->text);
      $view = 'components.' . $component->alias;
      $data = array_merge($vars, ['page' => $this, 'blockhash' => $blockhash]);
      $blocks[] = View::exists($view) ? View::make($view, $data)->render() : null;
    }

    return implode("\n", array_filter($blocks));
  }

  /**
   * Create an HTTP response that represents the object.
   *
   * @param  \Netflex\Http\Request $request
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function toResponse($request)
  {
    if ($template = $this->template) {
      return $template->toResponse([
        'request' => $request,
        'page' => $this
      ]);
    }
  }
}
