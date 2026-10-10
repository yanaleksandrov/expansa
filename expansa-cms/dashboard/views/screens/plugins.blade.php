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
<?php echo view('components/table/header', $table->headData()); ?>

@if($table->getData())
    <div class="plugins">
        @foreach($table->getData() as $item)
            <?php echo view($table->cells[0]->view, $item); ?>
        @endforeach
    </div>
@else
    <?php echo view('components/state', $table->notFoundData()); ?>
@endif
