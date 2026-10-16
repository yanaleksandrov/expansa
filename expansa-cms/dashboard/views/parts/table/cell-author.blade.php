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

$name  = is_scalar($value) ? (string) $value : '';
$color = $item['color'] ?? 'blue';
?>
@if ($name !== '')
    <div class="df aic g-2">
        <span class="avatar avatar--xs bg-{{ $color }}-lt t-{{ $color }}">{{ $item['initials'] ?? '' }}</span>
        <div>
            @if (! empty($item['author_id']))
                <a href="{{ url('dashboard/profile?id=' . $item['author_id']) }}" class="fw-500 t-dark">{{ $name }}</a>
            @else
                <strong>{{ $name }}</strong>
            @endif
            @if (! empty($item['email']))
                <div class="fs-12 t-muted mt-1">{{ $item['email'] }}</div>
            @endif
        </div>
    </div>
@endif
