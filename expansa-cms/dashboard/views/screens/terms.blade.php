<?php
/**
 * Terms list template can be overridden by copying it to themes/yourtheme/dashboard/views/screens/terms.php
 *
 * @var App\Tables\Terms $table
 *
 * @package Expansa\Templates
 */
?>
<?php echo view('components/table/header', $table->headData()); ?>

<div class="terms">
    <div class="terms-side">
        <?php echo form('terms-editor'); ?>
    </div>
    <div class="terms-main">
        @if($table->getData())
            <div class="table" u-data="table">
                <div class="table__head" style="{{ $table->style }}">
                    @foreach($table->cells as $cell)
                        <?php echo view('components/table/cell-head', [ 'cell' => $cell ]); ?>
                    @endforeach
                </div>
                @foreach($table->getData() as $item)
                    <div class="table__row" style="{{ $table->style }}">
                        @foreach($table->cells as $cell)
                            <?php echo view($cell->view, ['class' => $cell->key, ...$item]); ?>
                        @endforeach
                    </div>
                @endforeach
            </div>
        @else
            <?php echo view('components/state', $table->notFoundData()); ?>
        @endif

        <p>{{ t('Deleting a category does not delete the posts in that category. Instead, posts that were only assigned to the deleted category are set to the default category Uncategorized. The default category cannot be deleted.') }}</p>
    </div>
</div>
