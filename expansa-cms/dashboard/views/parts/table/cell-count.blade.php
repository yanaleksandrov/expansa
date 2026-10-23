<?php

use Expansa\Support\Arr;

/**
 * Number linking to the list it counts, e.g. the posts of a term; plain text without a URL.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/parts/table/cell-count.blade.php
 *
 * @var Expansa\Builders\Table\Cell $cell
 * @var Expansa\Builders\Table\Values\Link $value
 *
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;
?>
@if ($value->url)
    <a href="{{ $value->url }}" class="fw-500"{!! Arr::toHtmlAttributes($value->attributes) !!}>{{ $value->title }}</a>
@else
    <span class="t-muted">{{ $value->title }}</span>
@endif
