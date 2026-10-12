<?php
/**
 * Installed plugins list.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/screens/plugins.php
 *
 * @var App\Tables\Plugins $table
 *
 * @package Expansa\Templates
 */
?>
<div class="table" u-data="table" style="{{ $table->style }}">
    <div class="table__header">
        <?php echo view('components/table/header', $table->headData()); ?>

        <div class="table__head">
            @foreach($table->cells as $cell)
                <?php echo view('components/table/cell-head', ['cell' => $cell]); ?>
            @endforeach
        </div>
    </div>

    @if($table->getData())
        @foreach($table->getData() as $item)
            <div class="table__row hover" u-data="{{ json_encode(['item' => $item]) }}">
                @foreach($table->cells as $cell)
                    <div class="{{ $cell->key }}">
                        @include($cell->view, ['key' => $cell->key, 'attributes' => $cell->attributes, ...$item])
                    </div>
                @endforeach
            </div>
        @endforeach
    @else
        <?php echo view('components/state', $table->notFoundData()); ?>
    @endif
</div>
