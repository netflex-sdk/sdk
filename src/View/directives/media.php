<?php
echo (function ($file, $size, $mode = 'rc', $color = '0,0,0') {
  if (is_object($file)) {
    $file = $file->path ?? null;
  }

  $schema = Netflex\Foundation\Setting::get('site_cdn_protocol');
  $cdn = Netflex\Foundation\Setting::get('site_cdn_url');

  return "$schema://$cdn/media/$mode/$size/" . ($mode === 'fill' ? ($color . "/") : null) . $file;
})($expression);
?>
