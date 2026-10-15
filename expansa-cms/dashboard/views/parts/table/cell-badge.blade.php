<?php
use Expansa\Support\Arr;
use Expansa\Facades\Safe;

/**
 * Value in a badge, nothing for an empty one.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/parts/table/cell-badge.blade.php
 *
 * @var Expansa\Builders\Table\Cell $cell
 * @var array<string, mixed>        $item
 * @var mixed                       $value
 *
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;

[ $class, $icon, $text, $attributes ] = Safe::data(
    (array) ($value ?? []),
    [
        'class'      => 'class:badge',
        'icon'       => 'class:badge badge--dark',
        'value'      => 'trim',
        'attributes' => 'array',
    ]
)->values();
?>
@if ($icon || $value)
    <span class="{{ $class }}"{!! Arr::toHtmlAttributes($attributes) !!}>@if($icon)<i class="{{ $icon }}"></i>@endif{{ $text }}</span>
@endif
