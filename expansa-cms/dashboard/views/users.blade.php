<?php
/**
 * Users list template can be overridden by copying it to themes/yourtheme/dashboard/views/users.php
 *
 * @package Expansa\Templates
 */
if (!defined('EX_PATH')) {
    exit;
}

$table = new App\Tables\Users();
$items = $table->data ?? [];
$cells = $table->columns ?? [];
?>
<div class="expansa-main">
    <?php
    echo view('table/header', [
        'title'   => t('Users'),
        'actions' => true,
        'filter'  => true,
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
            'title'       => t('Users not found'),
            'description' => t('You don&apos;t have any users yet. <a @click="$dialog.open(`tmpl-post-editor`, postEditorDialog)">Add them manually</a> or [import via CSV](:importLink)', url('/dashboard/import')),
        ]);
        ?>
    @endif
</div>
