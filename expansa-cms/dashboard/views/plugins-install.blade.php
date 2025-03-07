<?php
/**
 * Addons list for install.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/plugins-install.php
 *
 * @package Expansa\Templates
 */
if (!defined('EX_PATH')) {
    exit;
}

$table = new App\Tables\PluginsInstall();
$items = $table->data ?? [];
$cells = $table->columns ?? [];
$column = $table->columns[0] ?? [];
?>
<div class="expansa-main">
    <?php
    echo view('table/header', [
        'title'  => t('Add Plugins'),
        'search' => true,
    ]);
    ?>
    @if($items)
        <div class="plugins">
            @foreach($items as $item)
                <?php echo view($column->view, $item); ?>
            @endforeach
        </div>
    @else
        <?php
        echo view('global/state', [
            'icon'        => 'no-plugins',
            'title'       => t('Plugins not found'),
            'description' => t('You don&apos;t have any themes installed yet, <a @click="$dialog.open(`tmpl-post-editor`)">download them</a>'),
        ]);
        ?>
    @endif

</div>
