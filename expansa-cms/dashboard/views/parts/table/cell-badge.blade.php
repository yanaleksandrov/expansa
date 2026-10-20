<?php
use Expansa\Support\Arr;

/**
 * Value in a badge, nothing for an empty one; the icon before the text is a dot by default.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/parts/table/cell-badge.blade.php
 *
 * @var Expansa\Builders\Table\Cell $cell
 * @var array<string, mixed>        $item
 * @var array{value?: string, class?: string, icon?: string, attributes?: array<string, mixed>} $value
 *
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;

$text  = $value['value'] ?? '';
$class = ($value['class'] ?? '') ?: 'badge';
$icon  = ($value['icon'] ?? '') ?: 'badge badge--dark';
?>
@if ($text !== '')
    <span class="{{ $class }}"{!! Arr::toHtmlAttributes($value['attributes'] ?? []) !!}><i class="{{ $icon }}"></i>{{ $text }}</span>
@endif
