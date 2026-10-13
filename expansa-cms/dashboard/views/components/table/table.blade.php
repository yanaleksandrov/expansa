<?php

use Expansa\Support\Arr;

/**
 * Table with the header, the column titles and a row per item, each cell rendered by the view
 * of its column; the not found state without items.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/components/table/table.blade.php
 *
 * @var Expansa\Builders\Table $table
 * @var bool                   $header Whether to show the header with the title and the tools, true by default.
 *
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;

$items = $table->getData();
?>
@if ($items)
<div class="table" u-data="table" style="{{ $table->style }}">
    <div class="table-head">
        @if ($header ?? true)
            <?php echo view('components/table/header', $table->headData()); ?>
        @endif
        <div class="table-head-data">
            @foreach ($table->cells as $cell)
                <?php echo view('components/table/cell-head', ['cell' => $cell]); ?>
            @endforeach
        </div>
    </div>
    <div class="table-body">
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
            <div class="table-row-data">
                Additional data
            </div>
        @endforeach
    </div>
</div>
@else
<?php echo view('components/state', $table->notFoundData()); ?>
@endif