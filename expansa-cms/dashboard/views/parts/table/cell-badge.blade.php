<?php

use Expansa\Support\Arr;

/**
 * Value in a badge, nothing for an empty one.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/parts/table/cell-badge.blade.php
 *
 * @var Expansa\Builders\Table\Cell $cell
 * @var Expansa\Builders\Table\Values\Badge $value
 *
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;
?>
<span class="{{ $value->class }}"{!! Arr::toHtmlAttributes($value->attributes) !!}><i class="{{ $value->icon }}"></i>{{ $value->text }}</span>
