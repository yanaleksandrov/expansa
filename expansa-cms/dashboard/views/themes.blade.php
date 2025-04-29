<?php
/**
 * Themes list template can be overridden by copying it to themes/yourtheme/dashboard/views/themes.php
 *
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;

$table = new App\Tables\Themes();
?>
<?php echo view('table/header', $table->headData()); ?>

@if($table->data)
    <div class="themes">
        @foreach($table->data as $item)
            <div class="themes-item">
                <?php echo view($table->cells[0]->view, $item); ?>
            </div>
        @endforeach
    </div>
@else
    <?php echo view('global/state', $table->notFoundData()); ?>
@endif
