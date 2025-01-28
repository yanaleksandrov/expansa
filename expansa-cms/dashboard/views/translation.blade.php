<?php
/**
 * Translation table.
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/translation.php
 *
 * @package Expansa\Templates
 */
if (!defined('EX_PATH')) {
    exit;
}
?>
<div class="expansa-main">
    <?php (new \Expansa\Builders\Table(new \app\Tables\Translations()))->print(); ?>
</div>
