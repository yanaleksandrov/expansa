<?php
/**
 * Comments list.
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/comments.php
 *
 * @package Expansa\Templates
 */
if (!defined('EX_PATH')) {
    exit;
}
?>
<div class="expansa-main">
    <?php (new \Expansa\Builders\Table(new \app\Tables\Comments()))->print(); ?>
</div>
