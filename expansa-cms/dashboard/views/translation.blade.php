<?php
/**
 * Translation table template can be overridden by copying it to themes/yourtheme/dashboard/views/translation.php
 *
 * @package Expansa\Templates
 */
if (!defined('EX_PATH')) {
    exit;
}
?>
<div class="expansa-main">
    <?php (new \Expansa\Builders\Table(new \App\Tables\Translations()))->print(); ?>
</div>
