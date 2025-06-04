<?php
/**
 * Addons list for install.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/plugins-install.php
 *
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;

$table = new App\Tables\PluginsInstall();
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
