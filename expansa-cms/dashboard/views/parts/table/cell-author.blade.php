<?php
/**
 * Author of an item: a small avatar with the initials, the name, linking to the profile of a registered author,
 * with the email below when there is one; nothing for an empty name.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/parts/table/cell-author.blade.php
 *
 * @var Expansa\Builders\Table\Cell $cell
 * @var array<string, mixed>        $item
 * @var mixed                       $value
 *
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;

$name      = is_scalar($value) ? (string) $value : '';
$color     = $item['color'] ?? 'blue';
$initials  = $item['initials'] ?? '';
$author_id = $item['author_id'] ?? 0;
$email     = $item['email'] ?? '';
?>
@if ($name !== '')
    <div class="df aic g-3">
        <span class="avatar avatar--rounded bg-{{ $color }}-lt t-{{ $color }}">{{ $initials }}</span>
        <div>
            @if ($author_id)
                <a href="{{ url("dashboard/profile?id=$author_id") }}" class="fw-500 t-dark">{{ $name }}</a>
            @else
                <strong>{{ $name }}</strong>
            @endif
            @if ($email)
                <div class="fs-12 t-muted mt-1">{{ $email }}</div>
            @endif
        </div>
    </div>
@endif
