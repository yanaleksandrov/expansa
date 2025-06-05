<?php
/**
 * Installed plugins list.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/plugins.php
 *
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;

$table = new App\Tables\Plugins();
?>
<?php echo view('table/header', $table->headData()); ?>

@if($table->data)
    <div class="plugins">
        @foreach($table->data as $item)
            <?php echo view($table->cells[0]->view, $item); ?>
        @endforeach
    </div>
@else
    <?php echo view('global/state', $table->notFoundData()); ?>
@endif
