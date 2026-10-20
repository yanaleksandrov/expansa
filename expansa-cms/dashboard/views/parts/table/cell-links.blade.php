<?php
/**
 * Comma-separated links, a link without a URL is plain text.
 * The first three are shown, the rest behind the "+N more" badge.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/parts/table/cell-links.blade.php
 *
 * @var Expansa\Builders\Table\Cell $cell
 * @var Expansa\Builders\Table\Values\Link[] $value
 *
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;

$more = count($value) - 3;
?>
<div>
    @foreach ($value as $i => $link)
        @if ($i === 3)
            <span class="table-more" hidden>
        @endif
        <span class="table-link">
            @if ($link->url)
                <a href="{{ $link->url }}">{{ $link->title }}</a>
            @else
                {{ $link->title }}
            @endif
        </span>
    @endforeach
    @if ($more > 0)
        </span>
        <span class="badge" u-bind="moreLinks">{{ t('+:count more', $more) }}</span>
    @endif
</div>
