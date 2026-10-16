<?php

use Expansa\Support\Arr;

/**
 * Title linking to `url` with the `actions` links shown on hover,
 * a list of `['title' => ..., 'href' => ..., 'class' => ...]` with optional `attributes`, e.g. `@click`.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/parts/table/cell-title.blade.php
 *
 * @var Expansa\Builders\Table\Cell $cell
 * @var array<string, mixed>        $item
 * @var mixed                       $value
 *
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;
?>
<a href="{{ $item['url'] ?? '#' }}" class="fw-500 t-dark">{{ is_scalar($value) ? $value : '' }}</a>
@if (! empty($item['actions']))
    <div class="df aic g-2 fs-12 mt-1 table-hidden">
        @foreach ($item['actions'] as $action)
            <a href="{{ $action['href'] ?? '#' }}" class="{{ $action['class'] ?? '' }}"{!! Arr::toHtmlAttributes($action['attributes'] ?? []) !!}>{{ $action['title'] ?? '' }}</a>
        @endforeach
    </div>
@endif
