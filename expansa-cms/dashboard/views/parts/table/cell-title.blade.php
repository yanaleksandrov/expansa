<?php

use Expansa\Support\Arr;

/**
 * Title linking to the page of the item, with the quick actions shown on hover.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/parts/table/cell-title.blade.php
 *
 * @var Expansa\Builders\Table\Cell $cell
 * @var Expansa\Builders\Table\Values\Title $value
 *
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;
?>
<a href="{{ $value->url }}" class="fw-500 t-dark">{{ $value->text }}</a>
@if ($value->actions)
    <div class="df aic g-2 fs-12 mt-1 table-hidden">
        @foreach ($value->actions as $action)
            <a href="{{ $action->url ?: '#' }}" class="{{ $action->class }}"{!! Arr::toHtmlAttributes($action->attributes) !!}>{{ $action->title }}</a>
        @endforeach
    </div>
@endif
