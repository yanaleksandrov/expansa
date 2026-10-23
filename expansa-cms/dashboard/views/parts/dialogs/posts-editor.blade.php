<?php
/**
 * Template for output posts editor.
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/parts/dialogs/posts-editor.php
 *
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;
?>
<!-- post editor template start -->
<template id="tmpl-post-editor" title="<?php echo t_attr('Post Editor'); ?>" size="lg" position="right" guarded @load="$dialog.restore($el)">
    <?php echo form('posts-creator'); ?>
</template>
