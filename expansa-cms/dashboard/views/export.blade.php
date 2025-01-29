<?php
/**
 * Export content template can be overridden by copying it to themes/yourtheme/dashboard/views/export.php
 *
 * @package Expansa\Templates
 */
if (!defined('EX_PATH')) {
    exit;
}
?>
<div class="expansa-main p-7 bg-gray-lt">
    <div class="mw-600 m-auto">
        <?php echo form('posts-export', EX_DASHBOARD . 'forms/posts-export.php'); ?>
    </div>
</div>
