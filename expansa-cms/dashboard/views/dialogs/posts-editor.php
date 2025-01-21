<?php
/**
 * Template for output posts editor.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/dialogs/posts-editor.php
 *
 * @package Expansa\Templates
 * @since   2025.1
 */
if ( ! defined( 'EX_PATH' ) ) {
	exit;
}
?>

<!-- post editor template start -->
<template id="tmpl-post-editor" x-init="$dialog.init(() => postEditorDialog)">
	<?php Dashboard\Form::print( EX_DASHBOARD . 'forms/posts-creator.php' ); ?>
</template>
