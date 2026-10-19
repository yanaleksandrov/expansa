<?php

use Expansa\Support\Arr;

/**
 * Rows of a table, each cell rendered by the view of its column; without rows the message of the filter
 * when it left out every row, else the state of Table::notFoundData(), e.g. of the empty trash.
 * Rendered by the table and by the API that applies the filter.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/parts/table/table-row.blade.php
 *
 * @var Expansa\Builders\Table $table
 *
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;

$items = $table->getData();
?>
@foreach ($items as $item)
    <div class="table-row">
        @foreach ($table->cells as $cell)
            <div{!! Arr::toHtmlAttributes(['class' => "table-cell table-cell--$cell->key", ...$cell->attributes]) !!}>
                <?php
                echo view(
                    $cell->view,
                    [
                        'cell'  => $cell,
                        'item'  => $item,
                        'value' => $item[$cell->key] ?? null
                    ]
                );
                ?>
            </div>
        @endforeach
    </div>
    @if(0)
        <div class="table-row-data">
            Additional data
        </div>
    @endif
@endforeach
@if (! $items && $table->activeFilters())
    <div class="table-filter-empty">{{ t('No items match the filter.') }}</div>
@elseif (! $items)
    <?php echo view('parts/state', $table->notFoundData()); ?>
@endif
