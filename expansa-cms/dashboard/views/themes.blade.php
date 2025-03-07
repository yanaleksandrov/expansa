<?php
/**
 * Themes list template can be overridden by copying it to themes/yourtheme/dashboard/views/themes.php
 *
 * @package Expansa\Templates
 */
if (!defined('EX_PATH')) {
    exit;
}

$table = new App\Tables\Themes();
?>
<div class="expansa-main">
    <?php
    echo view(
        'table/header',
        [
            'title' => t('Themes'),
        ]
    );

    echo '<pre>';
    print_r( $table );
    echo '</pre>';

    //(new Expansa\Builders\Table(new App\Tables\Themes()))->print();
    ?>
</div>
