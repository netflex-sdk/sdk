<?php

namespace Netflex\Builder;

use Netflex\Routing\Route;
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
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int $group_id
 * @property string $name
 * @property string $url
 * @property bool $children_inherit_url
 * @property Template|null $template
 * @property Controller|null $controller
 * @property Content $content
 * @property bool $published
 * @property int $revision
 * @property bool use_time
 * @property string|null $start
 * @property string|null $stop
 * @property bool $visible
 * @property bool $visible_nav
 * @property bool $visible_subnav
 * @property string $navtitle
 * @property bool $nav_hidden_xs
 * @property bool $nav_hidden_sm
 * @property bool $nav_hidden_md
 * @property bool $nav_hidden_lg
 * @property string $nav_target
 * @property string $type
 * @property Page[] $children
 * @property Page|null $parent
 * @property int $parent_id
 * @property string $title
 * @property int $sorting
 * @property string $add_to_head
 * @property string $add_to_bodyclose
 * @property string $body_class
 * @property bool $public
 * @property string $author
 * @property string $description
 * @property string $keywords
 * @property array $authgroups
 * @property string $domain
 */
class Page extends ReactiveObject implements Responsable
{
  use Retrievable;

  protected static $base_path = 'builder/pages';

  /** @var array */
  protected $timestamps = [];

  /** @var Route */
  public $route;

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
    return current_page();
  }

  /**
   * @return string
   */
  public function getTypeAttribute()
  {
    switch ($this->attributes['template']) {
      case 'e':
        return 'external';
      case 'i':
        return 'internal';
      case 'f':
        return 'folder';
      default:
        return 'page';
    }
  }

  /**
   * @param string $url
   * @return string
   */
  public function getUrlAttribute($url)
  {
    switch ($this->type) {
      case 'external':
        return $url;
      case 'internal':
        return static::retrieve($url)->url ?? '#';
      case 'f':
        return '#';
      default:
        return '/' . trim($url === 'index/' ? '/' : $url, '/');
    }
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
   * @return Controller|null
   */
  public function getControllerAttribute()
  {
    if ($this->hasTemplate()) {
      return $this->template->controller;
    }
  }

  public function getContentAttribute()
  {
    return Content::retrieve($this->id);
  }

  public function getParentIdAttribute()
  {
    return (int) $this->attributes['parent_id'];;
  }

  public function getParentAttribute()
  {
    if ($this->parent_id) {
      return static::retrieve($this->parent_id);
    }
  }

  public function getChildrenAttribute()
  {
    return static::all()->filter(function ($page) {
      return (int) $page->parent_id === (int) $this->id;
    })->values();
  }

  public function getContentRevision($revision)
  {
    return Content::retrieve($this->id, $revision);
  }

  /**
   * Undocumented function
   *
   * @param string $authgroups
   * @return array
   */
  public function getAuthGroupsAttribute($authgroups = '')
  {
    return array_map(
      'intval',
      array_values(
        array_filter(
          explode(',', $authgroups)
        )
      )
    );
  }

  public function getDomainAttribute() {
    if ($this->group_id) {
      return static::retrieve($this->group_id)->name;
    }
  }

  /**
   * Renders the given blocks
   *
   * @param string $area
   * @param array $vars
   * @return string
   */
  public function getBlocks($area, $vars = [], $id = null)
  {
    $page = $id ? Page::retrieve($id) : page() ?? $this;
    $blocks = [];

    $sections = $page->content->areas->filter(function ($contentArea) use ($area) {
      return $contentArea->area === $area;
    })->values();

    foreach ($sections as $section) {
      $blockVars = [];
      blockhash($section->title);

      $this->content->areas->each(function ($area) use (&$blockVars, $section) {
        if (preg_match("/^[\w\d_-]+_{$section->title}$/", $area->area)) {
          $area = Str::replaceLast('_'.blockhash(), '', trim($area->area, '_'));
          $blockVars[Str::camel($area)] = content($area);
          $blockVars[Str::snake($area)] = content($area);
        }
      });

      $pageVars = (page_variables() ?? []);
      $vars = array_merge($pageVars, $blockVars, $vars);

      $component = Template::get($section->text);
      $view = 'components.' . $component->alias;
      $data = array_merge($vars, ['blockhash' => $section->title]);
      $blocks[] = View::exists($view) ? trim(View::make($view, $data)->render()) : null;

      blockhash(null);
    }

    return implode("\n", array_filter($blocks));
  }

  /**
   * Create an HTTP response that represents the object.
   *
   * @param  \Illuminate\Http\Request $request
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function toResponse($request)
  {
    $vars = [];

    $data = [
      'request' => $request,
      'page' => $this
    ];

    $this->content->areas->each(function ($area) use (&$vars) {
      if (!preg_match('/^[\w\d_-]+_nf[\w\d_-]+$/', $area->area)) {
        $area = trim($area->area, '_');
        $vars[Str::camel($area)] = content($area);
        $vars[Str::snake($area)] = content($area);
      }
    });

    $response = '';
    page_variables(array_merge($vars, $data));

    if ($template = $this->template) {
      $response = $template->toResponse();
    }

    page_variables([]);

    return $response;
  }
}
