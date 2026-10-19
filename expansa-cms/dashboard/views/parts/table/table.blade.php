<?php

/**
 * Table with the header, the column titles and a row per item, each cell rendered by the view
 * of its column; the not found state without items, unless the filter or the search left them out.
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

// a search without results keeps the table, so that its field and chip stay to change it
$search = trim((string) ($_GET['s'] ?? ''));
?>
@if ($items || $active || $search !== '')
    <div class="table" u-data="table" @load="ajax = $ajax" style="{{ $table->style }}"@if ($filters) data-fields="{{ json_encode($filters) }}" data-rows="{{ json_encode($active) }}"@endif>
        <div class="table-head">
            @if ($header ?? true)
                <?php echo view('parts/table/header', [...$table->headData(), 'filters' => $filters]); ?>
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