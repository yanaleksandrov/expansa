<?php
/**
 * Media library dialog - a WordPress-style media picker/manager that can be opened from
 * anywhere in the dashboard with:
 *
 *     $dialog.open('tmpl-media-library', {
 *         title: 'Media Library',
 *         class: 'dialog--xl',
 *         multiple: false,   // or true for a gallery-style multi-select
 *         type: 'image',     // optional mime-family filter: image | video | audio | application
 *         onSelect(items) {  // `items` is a single post object, or an array when multiple
 *             ...
 *         },
 *     })
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/dialogs/media-library.php
 *
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;
?>
<!-- media library dialog start -->
<template id="tmpl-media-library">
    <?php echo view('parts/media-library'); ?>
</template>
