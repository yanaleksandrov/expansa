<?php
/**
 * Themes list template can be overridden by copying it to themes/yourtheme/dashboard/views/themes.php
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
            'title' => t('Themes'),
        ]
    );

    (new \Expansa\Builders\Table(new \App\Tables\Themes()))->print();
    ?>
</div>
