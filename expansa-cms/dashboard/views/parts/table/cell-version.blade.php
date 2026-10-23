<?php
/**
 * Installed version and, with an update, the arrow to the latest one and the badge of its kind.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/parts/table/cell-version.blade.php
 *
 * @var Expansa\Builders\Table\Cell $cell
 * @var Expansa\Builders\Table\Values\Version $value
 *
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;
?>
<span class="df aic g-1 fs-13">
    @if ($value->latest)
        <span class="t-muted">{{ $value->current }}</span>
        <i class="ph ph-arrow-right t-muted"></i>
        <span class="fw-500">{{ $value->latest }}</span>
        @if ($value->kind)
            <span class="{{ $value->kind->class }}">{{ $value->kind->text }}</span>
        @endif
    @else
        {{ $value->current }}
    @endif
</span>
