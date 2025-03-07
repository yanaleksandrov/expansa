<?php
/**
 * Themes list template can be overridden by copying it to themes/yourtheme/dashboard/views/themes.php
 *
 * @package Expansa\Templates
 */
if (!defined('EX_PATH')) {
    exit;
}

$table  = new App\Tables\Themes();
$items  = $table->data ?? [];
$column = $table->columns[0] ?? [];
?>
<div class="expansa-main">
    <?php
    echo view('table/header', [
        'title' => t('Themes'),
    ]);
    ?>
    @if($items)
        <div class="themes">
            @foreach($items as $item)
                <div class="themes-item">
                    <?php echo view($column->view, $item); ?>
                </div>
            @endforeach
        </div>
    @else
        <?php
        echo view('global/state', [
            'title'       => t('Themes not found'),
            'description' => t('You don\'t have any themes installed yet, <a @click="$dialog.open(`tmpl-post-editor`)">download them</a>'),
        ]);
        ?>
    @endif
</div>
