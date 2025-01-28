<?php
/**
 * Template for output posts editor.
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/dialogs/posts-editor.php
 *
 * @package Expansa\Templates
 */
if (!defined('EX_PATH')) {
    exit;
}
?>

        <!-- post editor template start -->
<template id="tmpl-post-editor" x-init="$dialog.init(() => postEditorDialog)">
    <?php echo form(EX_DASHBOARD . 'forms/posts-creator.php'); ?>
</template>
