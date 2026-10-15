<?php
/**
 * Plugin name with the description and the author.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/parts/table/cell-plugin.blade.php
 *
 * @var Expansa\Builders\Table\Cell $cell
 * @var array<string, mixed>        $item
 * @var mixed                       $value
 *
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;
?>
<div class="fs-15 lh-sm fw-500">{{ $item['title'] ?? '' }}</div>
<div class="mt-1 t-muted">{{ $item['description'] ?? '' }}</div>
<div class="df g-1 mt-1 t-muted">
    @if (! empty($item['author']))
        <span class="dib">{{ t('by :author', $item['author']) }}</span> ·
    @endif
    <a href="#" class="dib">{{ t('Settings') }}</a> ·
    <a href="#" class="dib t-red">{{ t('Delete') }}</a>
</div>
