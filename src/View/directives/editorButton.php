<?php
$__blockhash = $__blockhash ?? $blockhash ?? null;
$__editbuttons = $__editbuttons ?? [];

echo (function ($alias = null, $type = 'text', $field = null, $settings = [], $position = 'topright') use ($__blockhash, &$__editbuttons) {
  if (is_null($alias)) {
    return '';
  }

  $position = $position ?? 'topright';
  $class = "netflex-content-settings-btn netflex-content-btn-pos-$position ";
  $name = $settings['name'] ?? null;
  $title = $settings['label'] ?? $name ?? $alias;
  $alias .= $__blockhash ? ('_' . $__blockhash) : null;
  $maxItems = $settings['max-items'] ?? 99999;
  $config = null;
  $class .= $settings['class'] ?? null;
  $style = $settings['style'] ?? null;
  $icon = $settings['icon'] ?? null;
  $icon = "<span class=\"{$icon}\"></span>";
  $description = $settings['description'] ?? null;
  $field = $settings['content_field'] ?? null;
  $page = Netflex\Builder\Page::current();
  $class = trim($class);

  switch ($type) {
    case 'checkbox':
      $field = $field ?? 'text';
      break;
    case 'image':
      $field = $field ?? 'image';
      break;
    default:
      $field = $field ?? 'html';
      break;
  }

  if ($settings['config'] ?? false) {
    $config = base64_encode(serialize($settings['config']));
  }

  $__editbuttons[$alias] = (object) [
    'alias' => $alias,
    'field' => $field
  ];

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
})($expression);
?>
