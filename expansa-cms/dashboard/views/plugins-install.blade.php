<?php

/**
 * Addons list for install.
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/plugins-install.php
 *
 * @package Expansa\Templates
 */
if (!defined('EX_PATH')) {
    exit;
}
?>
<div class="expansa-main">
    <?php
    echo view(
        'table/header',
        [
            'title' => t('Add Plugins'),
            'search' => true,
        ]
    );

    (new \Expansa\Builders\Table(new \app\Tables\PluginsInstall()))->print();
    ?>
</div>
