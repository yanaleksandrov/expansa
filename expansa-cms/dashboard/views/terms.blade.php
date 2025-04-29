<?php
/**
 * Terms list template can be overridden by copying it to themes/yourtheme/dashboard/views/terms.php
 *
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;

$table = new App\Tables\Terms();
?>
<?php echo view('table/header', $table->headData()); ?>

<div class="terms">
    <div class="terms-side">
        <?php echo form('terms-editor', EX_DASHBOARD . 'forms/terms-editor.php'); ?>
    </div>
    <div class="terms-main">
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

        <p>{{ t('Deleting a category does not delete the posts in that category. Instead, posts that were only assigned to the deleted category are set to the default category Uncategorized. The default category cannot be deleted.') }}</p>
    </div>
</div>
