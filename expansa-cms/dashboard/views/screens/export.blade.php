<?php
/**
 * Export content template can be overridden by copying it to themes/yourtheme/dashboard/views/screens/export.php
 *
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;
?>
<div class="h-full bg-gray-lt p-7 md:px-5">
    <div class="mw-600 m-auto">
        <?php echo form('posts-export'); ?>
    </div>
</div>
