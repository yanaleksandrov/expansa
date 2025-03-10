<?php

use Expansa\Facades\Hook;

/**
 * Posts list template can be overridden by copying it to themes/yourtheme/dashboard/views/edit.php
 *
 * @package Expansa\Templates
 */
if (!defined('EX_PATH')) {
    exit;
}

$table = $__data['table'] ?? null;
if (! $table instanceof Expansa\Builders\Table) {
    return;
}

Hook::add('expansa_dashboard_footer', function () {
    echo view('dialogs/emails-editor');
});
?>
<div class="table" x-data="table" style="{{ $table->stylize($table->cells) }}">
    <div class="table__header">
        <?php echo view('table/header', $table->headData()); ?>

        <div class="table__head">
            @foreach($table->cells as $cell)
                <?php echo view('table/cell-head', ['cell' => $cell]); ?>
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
        <?php echo view('global/state', $table->notFoundData()); ?>
    @endif
</div>
