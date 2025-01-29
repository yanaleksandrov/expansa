<?php
/**
 * Dashboard settings template can be overridden by copying it to themes/yourtheme/dashboard/views/settings.php
 *
 * @package Expansa\Templates
 */
if (!defined('EX_PATH')) {
    exit;
}
?>
<div class="expansa-main">
    <!-- tabs start -->
    <?php echo form('settings', EX_DASHBOARD . 'forms/settings.php'); ?>
    <!-- tabs end -->
</div>
