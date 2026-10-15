<?php
/**
 * Comma-separated links: a list of `['title' => ..., 'href' => ...]`, a string is a single title.
 * The first three are shown, the rest behind the "+N more" badge.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/parts/table/cell-links.blade.php
 *
 * @var Expansa\Builders\Table\Cell $cell
 * @var array<string, mixed>        $item
 * @var mixed                       $value
 *
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;

$links = match (true) {
    is_array($value)                   => array_values($value),
    is_string($value) && $value !== '' => [['title' => $value]],
    default                            => [],
};
$more = count($links) - 3;
?>
<div u-data="{show: false}">
    @foreach ($links as $i => $link)
        @if ($i === 3)
            <span class="table-more" u-show="show" hidden>
        @endif
        <span class="table-link">
            @if (! empty($link['href']))
                <a href="{{ $link['href'] }}">{{ $link['title'] ?? '' }}</a>
            @else
                {{ $link['title'] ?? '' }}
            @endif
        </span>
    @endforeach
    @if ($more > 0)
        </span>
        <span class="badge" u-show="!show" @click="show = true">{{ t('+:count more', $more) }}</span>
    @endif
</div>
