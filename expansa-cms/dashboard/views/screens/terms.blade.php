<?php
/**
 * Terms list template can be overridden by copying it to themes/yourtheme/dashboard/views/screens/terms.php
 *
 * @var App\Tables\Terms $table
 *
 * @package Expansa\Templates
 */
?>
<?php echo view('parts/table/table-header', $table->headData()); ?>

<div class="terms">
    <div class="terms-side">
        <?php echo form('terms-editor'); ?>
    </div>
    <div class="terms-main">
        <?php echo view('parts/table/table', ['table' => $table, 'header' => false]); ?>

        <p>{{ t('Deleting a category does not delete the posts in that category. Instead, posts that were only assigned to the deleted category are set to the default category Uncategorized. The default category cannot be deleted.') }}</p>
    </div>
</div>
