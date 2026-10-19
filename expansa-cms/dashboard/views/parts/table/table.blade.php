<?php

/**
 * Table: the header of parts/table/header with the tools, the pagination and the filter,
 * the column titles and a row per item of the page, each cell rendered by the view of its column;
 * the not found state without items, unless the filter, the search or a part of the list left them out.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/parts/table/table.blade.php
 *
 * @var Expansa\Builders\Table $table
 * @var bool                   $header Whether to show the header with the title and the tools, true by default.
 *
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;

$items   = $table->getData();
$active  = $table->activeFilters();
$filters = $table->filters();

// a search or a part of the list without results keeps the table, so that their fields stay to change them
$search = trim((string) ($_GET['s'] ?? ''));
$head   = $table->headData();

// the options of the `table` component, see youla-expansa.js
$options = [
    'fields' => (object) $filters,
    'rows'   => $active,
    'parts'  => (object) array_map(fn (array $part) => (object) ($part['query'] ?? []), $head['parts'] ?? []),
];
?>
@if ($items || $active || $search !== '' || ($head['part'] ?? '') !== '')
    <div class="table" u-data="table({{ json_encode($options) }})" style="{{ $table->style }}">
        <div class="table-head">
            @if ($header ?? true)
                <?php echo view('parts/table/header', [...$head, 'table' => $table]); ?>
            @endif
            <div class="table-head-data">
                @foreach ($table->cells as $cell)
                    <?php echo view('parts/table/cell-head', ['cell' => $cell]); ?>
                @endforeach
            </div>
        </div>
        <div class="table-body">
            <?php echo view('parts/table/table-row', ['table' => $table]); ?>
        </div>
    </div>
@else
    <?php echo view('parts/state', $table->notFoundData()); ?>
@endif