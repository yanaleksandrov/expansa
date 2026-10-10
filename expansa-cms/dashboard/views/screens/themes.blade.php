<?php
/**
 * Themes list template can be overridden by copying it to themes/yourtheme/dashboard/views/screens/themes.php
 *
 * @var App\Tables\Themes $table
 *
 * @package Expansa\Templates
 */
?>
<?php echo view('components/table/header', $table->headData()); ?>

@if($table->getData())
    <div class="themes">
        @foreach($table->getData() as $item)
            <div class="themes-item">
                <?php echo view($table->cells[0]->view, $item); ?>
            </div>
        @endforeach
    </div>
@else
    <?php echo view('components/state', $table->notFoundData()); ?>
@endif
