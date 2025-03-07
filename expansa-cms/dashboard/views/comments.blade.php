<?php
/**
 * Comments list template can be overridden by copying it to themes/yourtheme/dashboard/views/comments.php
 *
 * @package Expansa\Templates
 */
if (!defined('EX_PATH')) {
    exit;
}

$table = new App\Tables\Comments();
$items = $table->data ?? [];
$cells = $table->columns ?? [];
?>
<div class="expansa-main">
    <?php
    echo view('table/header', [
        'title'   => t('Comments'),
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
            'title'       => t('No comments found'),
            'description' => t('Don&apos;t worry, they will appear as soon as someone leaves a comment.'),
        ]);
        ?>
    @endif
</div>
