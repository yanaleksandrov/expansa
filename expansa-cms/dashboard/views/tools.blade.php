<?php
/**
 * Dashboard tools template can be overridden by copying it to themes/yourtheme/dashboard/views/tools.php
 *
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;
?>
<div class="p-7">
    <?php echo form('tools-list', EX_DASHBOARD . 'forms/tools-list.php'); ?>
</div>
