<?php
/**
 * This file is part of Expansa CMS.
 *
 * @package Expansa\Templates
 */
if (!defined('EX_PATH')) {
    exit;
}

$table = new App\Tables\Plugins();
$items = $table->data ?? [];
$cells = $table->columns ?? [];
?>
<div class="expansa-main">
    <?php
    echo view('table/header', [
        'title'   => t('Plugins'),
        'actions' => true,
        'filter'  => true,
    ]);
    ?>

    @if($items)
        <div class="table" x-data="table" x-init="$ajax('extensions/get').then(response => console.log(response))">
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
            'icon'        => 'no-plugins',
            'title'       => t('Plugins are not installed yet'),
            'description' => t('You can download them manually or install from the repository'),
        ]);
        ?>
    @endif
</div>
