<?php

use Expansa\Facades\Safe;
use Expansa\Support\Arr;

/**
 * Breadcrumbs of a page: `items` with `label` text, optional `url` and `attributes` of the link; the last one
 * is the current page, a link only when it has the URL, e.g. of its public page.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/parts/breadcrumbs.blade.php
 *
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;

$items = Safe::array($__data['items'] ?? []);
$last  = array_key_last($items);
?>
<nav class="breadcrumbs" aria-label="{{ t_attr('Breadcrumbs') }}">
    @foreach ($items as $i => $item)
        <?php
        $attributes = [
            ...($item['attributes'] ?? []),
            ...($i === $last ? ['aria-current' => 'page'] : []),
        ];
        ?>
        @if (($item['url'] ?? '') !== '')
            <a{!! Arr::toHtmlAttributes(['href' => $item['url'], ...$attributes]) !!}>{{ $item['label'] }}</a>
        @else
            <span{!! Arr::toHtmlAttributes($attributes) !!}>{{ $item['label'] }}</span>
        @endif
        @if ($i !== $last)
            <i class="ph ph-caret-right"></i>
        @endif
    @endforeach
</nav>
