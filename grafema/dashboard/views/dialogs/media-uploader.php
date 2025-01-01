<?php
/**
 * Template for output media uploader.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/dialogs/media-uploader.php
 *
 * @package Grafema\Templates
 * @since   2025.1
 */
if ( ! defined( 'GRFM_PATH' ) ) {
	exit;
}
?>

<!-- media uploader template start -->
<template id="tmpl-media-uploader">
	<?php Dashboard\Form::print( GRFM_DASHBOARD . 'forms/grafema-files-uploader.php' ); ?>
</template>
