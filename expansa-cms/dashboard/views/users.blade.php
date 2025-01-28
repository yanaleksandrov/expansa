<?php
/**
 * Users list.
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/users.php
 *
 * @package Expansa\Templates
 */
if (!defined('EX_PATH')) {
    exit;
}
?>
<div class="expansa-main">
    <?php (new \Expansa\Builders\Table(new \app\Tables\Users()))->print(); ?>
</div>
