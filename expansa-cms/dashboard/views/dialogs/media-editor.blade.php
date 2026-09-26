<?php
/**
 * Template for output media editor.
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/dialogs/media-editor.php
 *
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;
?>
<!-- media editor template start -->
<template id="tmpl-media-editor" @load="$dialog.init('tmpl-media-editor', () => $ajax.post('media/get'))">
    <div class="media-editor">
        <div class="media-editor-main">
            <img class="media-editor-image" :src="entry.url" :alt="entry.filename" :width="entry.width" :height="entry.height">
        </div>
        <div class="media-editor-side">
            <div class="dg g-1 fs-12">
                <div><strong><?php echo t('Uploaded on'); ?>:</strong> <span u-text="entry.created"></span>
                </div>
                <div><strong><?php echo t('Uploaded by'); ?>:</strong> <span u-text="entry.author"></span></div>
                <div><strong><?php echo t('File name'); ?>:</strong> <span u-text="entry.filename"></span></div>
                <div><strong><?php echo t('File type'); ?>:</strong> <span u-text="entry.mime"></span></div>
                <div><strong><?php echo t('File size'); ?>:</strong> <span u-text="entry.sizeHumanize"></span>
                </div>
            </div>
            <?php echo form('media-editor', EX_DASHBOARD . 'forms/media-editor.php'); ?>
        </div>
    </div>
</template>
