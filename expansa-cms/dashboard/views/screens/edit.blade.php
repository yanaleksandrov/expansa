<?php

use Expansa\Facades\Hook;

/**
 * Posts list template can be overridden by copying it to themes/yourtheme/dashboard/views/screens/edit.php
 *
 * @var Expansa\Builders\Table\AbstractTable $table
 *
 * @package Expansa\Templates
 */

Hook::add('renderDashboardFooter', function () {
    echo view('components/dialogs/emails-editor');
});
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

    @if($table->data)
        @foreach($table->data as $item)
            <div class="table__row hover">
                @foreach($table->cells as $cell)
                    <div class="{{ $cell->key }}">
                        @include($cell->view, ['key' => $cell->key, ...$item])
                    </div>
                @endforeach
            </div>
        @endforeach
    @else
        <?php echo view('components/state', $table->notFoundData()); ?>
    @endif
</div>
