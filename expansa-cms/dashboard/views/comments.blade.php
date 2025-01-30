<?php
/**
 * Comments list template can be overridden by copying it to themes/yourtheme/dashboard/views/comments.php
 *
 * @package Expansa\Templates
 */
if (!defined('EX_PATH')) {
    exit;
}
?>
<div class="expansa-main">
    <?php (new \Expansa\Builders\Table(new \App\Tables\Comments()))->print(); ?>
</div>
