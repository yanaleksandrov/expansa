<?php
/**
 * Dashboard tools template can be overridden by copying it to themes/yourtheme/dashboard/views/tools.php
 *
 * @package Expansa\Templates
 */
if (!defined('EX_PATH')) {
    exit;
}
?>
<div class="expansa-main p-7 bg-gray-lt">
    <?php echo form('tools-list', EX_DASHBOARD . 'forms/tools-list.php'); ?>
</div>
