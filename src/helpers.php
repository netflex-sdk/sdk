<?php

use Illuminate\Support\Str;
use Netflex\Builder\Page;
use Netflex\Foundation\File;
use Netflex\Foundation\Setting;
use Netflex\Foundation\StaticContent;
use Netflex\Routing\Route;

if (!function_exists('block_reference')) {
  /**
   * Creates a unique reference for the given block context
   *
   * @param string $name
   * @return string
   */
  function block_reference ($name) {
    return trim(Str::snake($name) . '_' . blockhash(), '_');
  }
}

if (!function_exists('static_content')) {
  /**
   * @param string $block
   * @param string $area
   * @param string $field
   * @return mixed|void
   */
  function static_content($block, $area, $field = null)
  {
    $static = StaticContent::retrieve($block);
    $block = $static->globals->first(function ($item) use ($area) {
      return $item->alias === $area;
    });

    if ($block) {
      $field = $field ?? $block->content_type;
      return $block->content->{$field} ?? null;
    }
  }
}

if (!function_exists('container_binding')) {
  /**
   * Gets or sets the current blockhash
   *
   * @param string $abstract
   * @param array ...$args
   * @return string|void
   */
  function container_binding($abstract, ...$args)
  {
    if (empty($args)) {
      if (app()->has($abstract)) {
        return app()->get($abstract);
      }

      return null;
    }

    $value = array_shift($args) ?? null;

    app()->bind($abstract, function () use ($value) {
      return $value;
    });

    return $value;
  }
}

if (!function_exists('page_variables')) {
  /**
   * Gets or sets the JWT payload
   *
   * @param array $variables
   * @return array|void
   */
  function page_variables(...$args)
  {
    return container_binding('__page_variables__', ...$args);
  }
}

if (!function_exists('jwt_payload')) {
  /**
   * Gets or sets the JWT payload
   *
   * @param mixed $payload
   * @return mixed|void
   */
  function jwt_payload(...$args)
  {
    return container_binding('__jwt_payload__', ...$args);
  }
}

if (!function_exists('editor_tools')) {
  /**
   * Gets or sets the editor tools
   *
   * @param string $tools
   * @return string|void
   */
  function editor_tools(...$args)
  {
    return container_binding('__editor_tools__', ...$args);
  }
}

if (!function_exists('current_page')) {
  /**
   * Gets or sets the current page
   *
   * @param Page $page
   * @return Page|void
   */
  function current_page(...$args)
  {
    return container_binding('__page__', ...$args);
  }
}

if (!function_exists('blockhash')) {
  /**
   * Gets or sets the current blockhash
   *
   * @param string $hash
   * @return string|void
   */
  function blockhash(...$args)
  {
    return container_binding('__blockhash__', ...$args);
  }
}

if (!function_exists('mode')) {
  /**
   * Gets or sets the current mode
   *
   * @param string $mode
   * @return string|void
   */
  function mode(...$args)
  {
    return container_binding('__mode__', ...$args);
  }
}

if (!function_exists('page')) {
  /**
   * Retrieve current page, or a page identified by id
   *
   * @param int? $id
   * @return Page|null
   */
  function page($id = null)
  {
    return !is_null($id) ? Page::retrieve($id) : Page::current();
  }
}

if (!function_exists('resolve_type')) {
  /**
   * Resolve primary field for content area
   *
   * @param string $type
   * @return string
   */
  function resolve_type($type)
  {
    switch ($type) {
      case 'image':
        return 'image';
      case 'color':
      case 'entries':
      case 'text':
      case 'select':
      case 'checkbox':
      case 'multiselect':
        return 'text';
      case 'contentlist':
      case 'gallery':
      case 'file':
      case 'nav':
        dd($type);
      default:
        return 'html';
    }
  }
}

if (!function_exists('content')) {
  /**
   * Retrieve current page, or a page identified by id
   *
   * @param string $area
   * @param string $field
   * @return mixed
   */
  function content($area, $field = 'auto')
  {
    $area = trim($area . '_' . blockhash(), '_');

    $content = page()->content->areas->first(function ($contentArea) use ($area) {
      return $contentArea->area === $area;
    });

    if ($content) {
      $field = $field === 'auto' ? resolve_type($content->type) : $field;
      return property_exists($content, $field) ? $content->{$field} : $content;
    }

    return $field === 'checkbox' ? false : ($field === 'contentlist' ? [] : null);
  }
}

if (!function_exists('picture_raw')) {
  /**
   * @param array $settings
   * @return object
   */
  function picture_raw(array $settings = [])
  {
    $path = $settings['path'] ?? '';
    $path = is_object($path) ? $path->path : $path;
    $alt = $settings['alt'] ?? '';
    $title = $settings['title'] ?? '';
    $alt = $alt ?? $title ?? '';
    $title = $title ?? $alt ?? '';
    $size = $settings['dimensions'] ?? '1x1';
    $type = $settings['compression'] ?? 'rc';
    $color = $settings['fill'] ?? '0,0,0';
    $resolutions = $settings['resolutions'] ?? ['1x'];
    $resolutions = !is_array($resolutions) ? [$resolutions] : $resolutions;

    $resolutions = array_map(function ($resolution) {
      if (is_int($resolution) || is_float($resolution)) {
        return "{$resolution}x";
      }

      return $resolution;
    }, $resolutions);

    $breakpoints = $settings['breakpoints'] ?? [
      'xxs' => 320,
      'xs' => 480,
      'sm' => 768,
      'md' => 992,
      'lg' => 1200,
      'xl' => 1440,
      'xxl' => 1920
    ];

    $breakpoints = !is_array($breakpoints) ? [] : $breakpoints;
    $breakpoints['xxs'] = $breakpoints['xxs'] ?? $breakpoints['xs'] ?? 480;

    if (!in_array('1x', $resolutions)) {
      array_unshift($resolutions, '1x');
    }

    $srcsets = [];
    foreach ($breakpoints as $breakpoint => $maxWidth) {
      $url = implode(', ', array_map(function ($resolution) use ($path, $size, $type, $color, $maxWidth) {
        $url = media($path, $size, $type, $color) . "?src={$maxWidth}w";
        return "$url&res=$resolution $resolution";
      }, $resolutions));

      $srcsets[$breakpoint] = (object) [
        'path' => $path,
        'resolutions' => $resolutions,
        'compression' => $type,
        'fill' => $color,
        'dimensions' => $size,
        'maxwidth' => $maxWidth,
        'url' => "$url"
      ];
    }

    return (object) [
      'srcset' => $srcsets,
      'path' => media($path, $size, $type, $color)
    ];
  }
}

if (!function_exists('picture')) {
  /**
   * @param array|string $settings|$path
   * @param string? $size
   * @param string? $type
   * @return string
   */
  function picture($settings = [], ...$args)
  {
    if (is_string($settings)) {
      $settings = [
        'path' => $settings,
        'dimensions' => count($args) > 0 ? $args[0] : null,
        'compression' => count($args) > 1 ? $args[1] : null
      ];
    }

    $picture_class = $settings['picture_class'] ?? '';
    $image_class = $settings['image_class'] ?? '';
    $image_style = $settings['image_style'] ?? '';
    $path = $settings['path'] ?? null;
    $path = is_object($path) ? $path->path : $path;
    $size = $settings['dimensions'] ?? '1x1';
    $type = $settings['compression'] ?? 'rc';
    $color = $settings['fill'] ?? '0,0,0';
    $title = $settings['title'] ?? '';
    $alt = $settings['alt'] ?? $title ?? '';
    $title = $title ?? $alt ?? '';
    $resolutions = $settings['resolutions'] ?? null;
    $breakpoints = $settings['breakpoints'] ?? null;

    $src = media($path, $size, $type, $color);
    $srcsets = [];

    foreach (picture_raw([
      'path' => $path,
      'dimensions' => $size,
      'compression' => $type,
      'fill' => $color,
      'resolutions' => $resolutions,
      'breakpoints' => $breakpoints
    ])->srcset as $srcset) {
      $srcsets[] = <<<HTML
<source srcset="{$srcset->url}" media="(max-width: {$srcset->maxwidth}px)">
HTML;
    }

    $srcsets = implode("\n", $srcsets);

    return <<<HTML
<picture class="$picture_class">
  {$srcsets}
  <img class="$image_class" src="$src" alt="$alt" title="$title" style="$image_style" />
</picture>
HTML;
  }
}

if (!function_exists('image')) {
  function image($path, $size, $type = 'rc', $class = null, $alt = null, $title = null, $color = '0,0,0') {
    $alt = $alt ?? $title ?? '';
    $title = $title ?? $alt ?? '';
    $url = media($path, $size, $type, $color);
    return <<<HTML
<img class="$class" src="$url" $title="$title" alt="$alt">
HTML;
  }
}

if (!function_exists('media')) {
  /**
   * Get URL to a CDN image
   *
   * @param File|string $file
   * @param array|string|int $size
   * @param string $type
   * @param array|string|int $color
   * @return string
   */
  function media($file, $size = null, $type = 'rc', $color = '0,0,0', ...$gb)
  {
    if (is_object($file)) {
      $file = $file->path ?? null;
    }

    $schema = setting('site_cdn_protocol');
    $cdn = setting('site_cdn_url');

    $size = (is_string($size) && !(strpos($size, 'x') > 0)) ? "{$size}x{$size}" : $size;
    $size = is_float($size) ? floor($size) : $size;
    $size = is_int($size) ? "{$size}x{$size}" : $size;

    $width = is_array($size) ? floor(($size[0] ?? 0)) : 0;
    $height = is_array($size) ? floor(($size[1] ?? 0)) : 0;
    $size = is_array($size) ? "{$width}x{$height}" : $size;

    $fill = null;

    if ($type === 'fill') {
      if ((is_string($color) || (is_int($color) || is_float($color))) && count($gb)) {
        $color = [$color, $gb[0] ?? 0, $gb[1] ?? 0];
      }

      if (is_string($color)) {
        $color = explode(',', $color);
      }

      if (is_int($color) || is_float($color)) {
        $color = floor($color % 256);
        $color = "$color,$color,$color";
      }

      if (is_array($color)) {
        $r = floor((intval($color[0] ?? 0)) % 256);
        $g = floor((intval($color[1] ?? 0)) % 256);
        $b = floor((intval($color[2] ?? 0)) % 256);
        $color = "$r,$g,$b";
      }

      $fill = $color . "/";
    }

    return "$schema://$cdn/media/$type/$size/{$fill}{$file}";
  }
}

if (!function_exists('setting')) {
  /**
   * Retrieve the value of a setting
   *
   * @param string $key
   * @return mixed
   */
  function setting($key)
  {
    return Setting::get($key);
  }
}

if (!function_exists('editor_button')) {
  /**
   * Creates an editor button
   *
   * @param string $area
   * @param array $settings
   * @param string $position
   * @param string $type
   * @param string $field
   * @return string
   */
  function editor_button($area, $type = 'text', $settings = [], $position = 'topright', $field = 'auto')
  {
    $position = $position ?? 'topright';
    $class = "netflex-content-settings-btn netflex-content-btn-pos-$position ";
    $name = $settings['name'] ?? null;
    $title = $settings['label'] ?? $name ?? $area;
    $alias = trim($area . '_' . blockhash(), '_');
    $maxItems = $settings['max-items'] ?? 99999;
    $config = null;
    $class .= $settings['class'] ?? null;
    $style = $settings['style'] ?? null;
    $icon = $settings['icon'] ?? null;
    $icon = "<span class=\"{$icon}\"></span>";
    $description = $settings['description'] ?? null;
    $field = $settings['content_field'] ?? null;
    $page = page();
    $class = trim($class);

    $type = $type ?? content($area, 'type');
    $field = $field ?? resolve_type($type);

    if ($settings['config'] ?? false) {
      $config = base64_encode(serialize($settings['config']));
    }

    return <<<HTML
<a href="#"
   class="$class"
   style="$style"
   data-area-name="$name"
   data-area-field="$field"
   data-area-description="$description"
   data-page-id="{$page->id}"
   data-area-config="$config"
   data-area-type="$type"
   data-area-alias="$alias"
   data-max-items="$maxItems"
>$icon $title</a>
HTML;
  }
}

if (!function_exists('nav')) {
  /**
   * Generates a nav
   *
   * @param int $parent
   * @param int $levels
   * @param string $class
   * @param string $type
   * @param string $root
   * @param string $li
   * @param string $a
   * @return string
   */
  function nav($parent = null, $levels = 1, $class = null, $type = 'nav', $root = null, $li = null, $a = null)
  {
    $parent = page(($parent ?? page()->id ?? null));
    $children = [];
    $route = request()->route();

    if ($parent && $levels > 0) {
      foreach ($parent->children as $child) {
        $children[] = (function ($child) use ($route, $levels, $type, $root, $li, $a) {
          if ($child->{"visible_$type"}) {
            $url = null;
            $target = '_self';
            $title = $child->navtitle ? $child->navtitle : $child->name;
            $classList = [$a];

            if (get_class($route) === Route::class && $route->data('page')->id === $child->id) {
              $classList[] = 'active';
            }

            $url = $child->url;

            if ($child->type === 'folder') {
              $class[] = 'navfolder';
            }

            foreach (['xs', 'sm', 'md', 'lg'] as $breakpoint) {
              if ($child->{"nav_hidden_$breakpoint"}) {
                $classList[] = "hidden-$breakpoint";
              }
            }

            $class = implode(' ', array_filter($classList));

            $childItems = ($levels >= 2)
              ? nav(
                $child->id,
                $levels - 1,
                'dropdown-container',
                $type,
                $root,
                $li,
                $a
              )
              : null;

            return <<<HTML
<li class="$li $class">
  <a href="$url" class="$class" target="$target" role="menuitem">
    $title
  </a>
  $childItems
</li>
HTML;
          }
        })($child);
      }
    }

    $children = implode("\n", array_filter($children));

    return <<<HTML
<ul class="$class" role="menu">
  $children
</ul>
HTML;
  }
}
