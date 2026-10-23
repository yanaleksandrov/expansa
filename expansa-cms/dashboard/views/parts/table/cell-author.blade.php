<?php
/**
 * Author of an item: a small avatar with the initials, the name, linking to the profile of a registered author,
 * with the email below when there is one; nothing for an empty name.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/parts/table/cell-author.blade.php
 *
 * @var Expansa\Builders\Table\Cell $cell
 * @var Expansa\Builders\Table\Values\Person $value
 *
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;
?>
<div class="df aic g-3">
    <span class="avatar avatar--rounded bg-{{ $value->color }}-lt t-{{ $value->color }}">{{ $value->initials }}</span>
    <div>
        @if ($value->url)
            <a href="{{ $value->url }}" class="fw-500 t-dark">{{ $value->name }}</a>
        @else
            <strong>{{ $value->name ?: '-' }}</strong>
        @endif
        @if ($value->email)
            <div class="fs-12 t-muted mt-1">{{ $value->email }}</div>
        @endif
    </div>
</div>
