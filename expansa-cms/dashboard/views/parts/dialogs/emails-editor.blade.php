<?php

/**
 * Template for output emails editor.
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/parts/dialogs/emails-editor.php
 *
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;
?>
<!-- email editor template start -->
<template id="tmpl-email-editor" @load="$dialog.xl.right.init('tmpl-email-editor', () => emailDialog)">
    <div class="email">
        <div class="email-form">
            <?php echo form('emails-creator'); ?>
        </div>
        <div class="email-preview">
            <?php
            echo view(
                'mails/wrappers',
                [
                    'body_template' => 'mails/reset-password',
                ]
            );
            ?>
        </div>
    </div>
</template>
