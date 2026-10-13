<?php

use Expansa\Facades\Safe;

/**
 * Column title in the table head; a sortable one links to the page sorted by it, `orderby` and `order`
 * in the query, a second click reverses the order.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/components/table/cell-head.blade.php
 *
 * @var Expansa\Builders\Table\Cell $cell
 *
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;

$isSorted    = $cell->sortable && Safe::text($_GET['orderby'] ?? '') === $cell->key;
$isAscending = $isSorted && ($_GET['order'] ?? '') === 'asc';
?>
@if ($cell->sortable)
    <a class="{{ $cell->key }} df aic g-1" href="?{{ http_build_query([...$_GET, 'orderby' => $cell->key, 'order' => $isAscending ? 'desc' : 'asc']) }}">
        {!! $cell->title !!}
        <i class="ph {{ $isSorted ? ($isAscending ? 'ph-sort-ascending' : 'ph-sort-descending') : 'ph-arrows-down-up' }}"></i>
    </a>
@else
    <div class="{{ $cell->key }} df aic g-1">
        {!! $cell->title !!}
    </div>
@endif
