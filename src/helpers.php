<?php

use Netflex\Builder\Page;
use Netflex\Foundation\File;
use Netflex\Foundation\Setting;

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

if (!function_exists('content')) {
  /**
   * Retrieve current page, or a page identified by id
   *
   * @param string $area
   * @param string $field
   * @param string? $hash
   * @return mixed
   */
  function content($area, $field = 'html', $hash = null)
  {
    $app = app();
    $hash = $hash ?? $app->has('__blockhash') ? $app->get('__blockhash') : null;
    $area = $area . ($hash ? ('_' . $hash) : null);
    $field = $field ?? 'html';

    $content = page()->content->areas->first(function ($contentArea) use ($area) {
      return $contentArea->area === $area;
    });

    if ($content) {
      return $content->{$field} ?? null;
    }

    return null;
  }
}

if (!function_exists('media')) {
  /**
   * Get URL to a CDN image
   *
   * @param File|string $file
   * @param string $size
   * @param string $type
   * @param string $color
   * @return string
   */
  function media($file, $size, $type = 'rc', $color = '0,0,0')
  {
    if (is_object($file)) {
      $file = $file->path ?? null;
    }

    $schema = setting('site_cdn_protocol');
    $cdn = setting('site_cdn_url');

    return "$schema://$cdn/media/$type/$size/" . ($type === 'fill' ? ($color . "/") : null) . $file;
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
