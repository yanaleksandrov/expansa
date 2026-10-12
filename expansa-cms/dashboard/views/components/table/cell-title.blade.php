<?php
/**
 * Title linking to `url` with the `status` after it and the `actions` links shown on hover,
 * a list of `['title' => ..., 'href' => ..., 'class' => ...]`.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/components/table/cell-title.blade.php
 *
 * @var Expansa\Builders\Table\Cell $cell
 * @var array<string, mixed>        $item
 * @var mixed                       $value
 *
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;
?>
<div class="fs-14 lh-sm">
    <a href="{{ $item['url'] ?? '#' }}" class="fw-500 t-dark">{{ is_scalar($value) ? $value : '' }}</a>
    @if (! empty($item['status']))
        <span class="t-muted">— {{ $item['status'] }}</span>
    @endif
</div>
@if (! empty($item['actions']))
    <div class="df aic g-2 mt-1 table-hidden">
        @foreach ($item['actions'] as $action)
            <a href="{{ $action['href'] ?? '#' }}" class="{{ $action['class'] ?? '' }}">{{ $action['title'] ?? '' }}</a>
        @endforeach
    </div>
@endif
