<?php

/**
 * Template for API keys manager.
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/dialogs/api-keys-manager.php
 *
 * @package Expansa\Templates
 */
if (!defined('EX_PATH')) {
    exit;
}
?>
<!-- email editor template start -->
<template id="tmpl-api-keys-manager">
    <?php echo form('api-keys-manager', EX_DASHBOARD . 'forms/api-keys-manager.php'); ?>
</template>
