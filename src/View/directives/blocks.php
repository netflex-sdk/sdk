<?php
$__page = $__page ?? $page ?? Netflex\Builder\Page::current();
echo $__page->getBlocks($expression);
?>
