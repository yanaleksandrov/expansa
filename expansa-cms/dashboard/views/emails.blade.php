<?php

use Expansa\Facades\Hook;

/**
 * Emails template can be overridden by copying it to themes/yourtheme/dashboard/views/emails.php
 *
 * @version 2025.1
 */
if (!defined('EX_PATH')) {
    exit;
}

Hook::add('expansa_dashboard_footer', function () {
    echo view('dialogs/emails-editor');
});

$table = new App\Tables\Emails();
$items = $table->data ?? [];
$cells = $table->columns ?? [];
?>
<div class="expansa-main">
    <?php
    echo view('table/header', [
        'title'   => t('Emails'),
        'actions' => true,
    ]);
    ?>

    @if($items)
        <div class="table" x-data="table" @change="$ajax('users/get').then(response => items = response.items)">
            <div class="table__head" style="{{ $table->stylize($cells) }}">
                @foreach($cells as $cell)
                    <?php echo view('table/cell-head', ['cell' => $cell]); ?>
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
            'title'       => t('No emails templates found'),
            'description' => t('Add [new email template](:emailDialog) manually', url('/dashboard/import')),
        ]);
        ?>
    @endif
</div>
