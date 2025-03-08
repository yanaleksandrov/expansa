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
<?php echo view('table/header', $table->headData()); ?>

@if($table->data)
    <div class="table" x-data="table">
        <div class="table__head" style="{{ $table->stylize($table->cells) }}">
            @foreach($table->cells as $cell)
                <?php echo view('table/cell-head', [ 'cell' => $cell ]); ?>
            @endforeach
        </div>
        @foreach($table->data as $item)
            <div class="table__row" style="{{ $table->stylize($table->cells) }}">
                @foreach($table->cells as $cell)
                    <?php echo view($cell->view, ['class' => $cell->key, ...$item]); ?>
                @endforeach
            </div>
        @endforeach
    </div>
@else
    <?php echo view('global/state', $table->notFoundData()); ?>
@endif
