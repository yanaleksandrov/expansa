<?php

use Expansa\Facades\Hook;

/**
 * Pages list template can be overridden by copying it to themes/yourtheme/dashboard/views/pages.php
 *
 * @package Expansa\Templates
 */
if (!defined('EX_PATH')) {
    exit;
}

$table = new App\Tables\Pages();
$items = $table->data ?? [];
$cells = $table->columns ?? [];

Hook::add('expansa_dashboard_footer', function () {
    echo view('dialogs/posts-editor');
});
?>
<div class="expansa-main">
    <?php
    echo view('table/header', [
        'title'   => t('Pages'),
        'actions' => true,
        'filter'  => true,
    ]);
    ?>

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
</div>
