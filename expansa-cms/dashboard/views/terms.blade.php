<?php
/**
 * Terms list template can be overridden by copying it to themes/yourtheme/dashboard/views/terms.php
 *
 * @package Expansa\Templates
 */
if (!defined('EX_PATH')) {
    exit;
}

$table = new App\Tables\Terms();
$items = $table->data ?? [];
$cells = $table->columns ?? [];
?>
<div class="expansa-main">
    <?php
    echo view('table/header', [
        'title'   => t('Terms'),
        'actions' => true,
        'filter'  => true,
    ]);
    ?>
    <div class="terms">
        <div class="terms-side">
            <?php echo form('terms-editor', EX_DASHBOARD . 'forms/terms-editor.php'); ?>
        </div>
        <div class="terms-main">
            @if($items)
                <div class="table" x-data="table">
                    <div class="table__head" style="{{ $table->stylize($cells) }}">
                        @foreach($cells as $cell)
                            <?php echo view('table/cell-head', [ 'cell' => $cell ]); ?>
                        @endforeach
                    </div>
                    @foreach($items as $item)
                        <div class="table__row" style="{{ $table->stylize($cells) }}">
                            @foreach($cells as $cell)
                                <?php echo view($cell->view, ['class' => $cell->key, ...$item]); ?>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            @else
                <?php
                echo view('global/state', [
                    'title'       => t('Pages not found'),
                    'description' => t('You don&apos;t have any pages yet. <a @click="$dialog.open(`tmpl-post-editor`, postEditorDialog)">Add them manually</a> or [import via CSV](:importLink)', url('/dashboard/import')),
                ]);
                ?>
            @endif

            <p>{{ t('Deleting a category does not delete the posts in that category. Instead, posts that were only assigned to the deleted category are set to the default category Uncategorized. The default category cannot be deleted.') }}</p>
        </div>
    </div>
</div>
