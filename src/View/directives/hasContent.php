<?php
$__editbuttons = $__editbuttons ?? [];
$__blockhash = $__blockhash ?? $blockhash ?? null;
$__page = $__page ?? $page ?? Netflex\Builder\Page::current();

if ((function ($area, $field = null) use ($__blockhash, $__page, &$__editbuttons) {
  $area .= $__blockhash ? ('_' . $__blockhash) : null;

  if (is_null($field) && isset($__editbuttons[$area])) {
    $field = $__editbuttons[$area]->field;
  }

  $field = $field ?? 'html';

  if (!isset($__editbuttons[$area])) {
    $__editbuttons[$area] = (object) [
      'alias' => $area,
      'field' => $field,
    ];
  }

  $content = $__page->content->areas->first(function ($contentArea) use ($area) {
    return $contentArea->area === $area;
  });

  if ($content) {
    return (bool)($content->{$field} ?? false);
  }

  return false;
})($expression)):
?>
