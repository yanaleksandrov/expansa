<?php
/**
 * Themes list template can be overridden by copying it to themes/yourtheme/dashboard/views/screens/themes.php
 *
 * @var App\Tables\Themes $table
 *
 * @package Expansa\Templates
 */

$cell = $table->cells[0];
?>
<?php echo view('parts/table/header', $table->headData()); ?>

@if ($table->getData())
    <div class="themes">
        @foreach ($table->getData() as $item)
            <?php echo view($cell->view, ['cell' => $cell, 'item' => $item, 'value' => $item[$cell->key] ?? null]); ?>
        @endforeach
    </div>
@else
    <?php echo view('parts/state', $table->notFoundData()); ?>
@endif
