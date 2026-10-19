<?php

/**
 * Column title in the table head; a sortable one sorts the rows by it through the `sortLink` binding of the `table`
 * component, `orderby` and `order` in the query, a second click reverses the order. The link keeps the URL
 * of the sorted page for opening it in a new tab.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/parts/table/cell-head.blade.php
 *
 * @var Expansa\Builders\Table\Cell $cell
 * @var array{by: string, order: string} $sorting Sorting of the table, see Table::sorting().
 * @var array                       $query   Query of the page the link keeps, `$_GET` by default.
 *
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;

$sorting     = $sorting ?? ['by' => '', 'order' => 'desc'];
$query       = $query ?? $_GET;
$isSorted    = $cell->sortable && $sorting['by'] === $cell->key;
$isAscending = $isSorted && $sorting['order'] === 'asc';
$next        = $isAscending ? 'desc' : 'asc';
// the separators of the filters stay readable, see Table::activeFilters()
$href        = '?' . str_replace(['%2C', '%21'], [',', '!'], http_build_query([...$query, 'orderby' => $cell->key, 'order' => $next, 'page' => null]));
?>
@if ($cell->sortable)
    <a class="{{ $cell->key }} df aic g-1" href="{{ $href }}" u-bind="sortLink" data-orderby="{{ $cell->key }}" data-order="{{ $next }}">
        {!! $cell->title !!}
        <i class="ph {{ $isSorted ? ($isAscending ? 'ph-sort-ascending' : 'ph-sort-descending') : '' }}"></i>
    </a>
@else
    <div class="{{ $cell->key }} df aic g-1">
        {!! $cell->title !!}
    </div>
@endif
