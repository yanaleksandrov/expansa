<?php
/**
 * Template for output media editor.
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/dialogs/media-editor.php
 *
 * @package Expansa\Templates
 */
if (!defined('EX_PATH')) {
    exit;
}
?>

        <!-- media editor template start -->
<template id="tmpl-media-editor" x-init="$dialog.init(() => $ajax('media/get'))">
    <div class="media-editor">
        <div class="media-editor-main">
            <img class="media-editor-image" :src="$store.dialog.url" :alt="$store.dialog.filename"
                 :width="$store.dialog.width" :height="$store.dialog.height">
        </div>
        <div class="media-editor-side">
            <div class="dg g-1 fs-12">
                <div><strong><?php echo t('Uploaded on'); ?>:</strong> <span x-text="$store.dialog.created"></span>
                </div>
                <div><strong><?php echo t('Uploaded by'); ?>:</strong> <span x-text="$store.dialog.author"></span></div>
                <div><strong><?php echo t('File name'); ?>:</strong> <span x-text="$store.dialog.filename"></span></div>
                <div><strong><?php echo t('File type'); ?>:</strong> <span x-text="$store.dialog.mime"></span></div>
                <div><strong><?php echo t('File size'); ?>:</strong> <span x-text="$store.dialog.sizeHumanize"></span>
                </div>
                <div><strong><?php echo t('Length'); ?>:</strong> 2 minutes, 48 seconds</div>
            </div>
            <?php echo form(EX_DASHBOARD . 'forms/media-editor.php'); ?>
        </div>
    </div>
</template>
