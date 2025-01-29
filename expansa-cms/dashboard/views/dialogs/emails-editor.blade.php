<?php

/**
 * Template for output emails editor.
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/dialogs/emails-editor.php
 *
 * @package Expansa\Templates
 */
if (!defined('EX_PATH')) {
    exit;
}
?>
<!-- email editor template start -->
<template id="tmpl-email-editor" x-init="$dialog.init(() => emailDialog)">
    <div class="email">
        <div class="email-form">
            <?php echo form('emails-creator', EX_DASHBOARD . 'forms/emails-creator.php'); ?>
        </div>
        <div class="email-preview">
            <?php
            echo view(
                'views/mails/wrappers',
                [
                    'body_template' => 'views/mails/reset-password',
                ]
            );
            ?>
        </div>
    </div>
</template>
