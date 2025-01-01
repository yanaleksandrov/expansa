<?php
/**
 * Export website content page
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/export.php
 *
 * @package Grafema\Templates
 * @since   2025.1
 */
if ( ! defined( 'GRFM_PATH' ) ) {
	exit;
}
?>
<div class="grafema-main p-7 bg-gray-lt">
	<div class="mw-600 m-auto">
		<?php Dashboard\Form::print( GRFM_DASHBOARD . 'forms/posts-export.php' ); ?>
	</div>
</div>