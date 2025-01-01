<?php
/**
 * Grafema dashboard settings page.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/settings.php
 *
 * @package Grafema\Templates
 * @since   2025.1
 */
if ( ! defined( 'GRFM_PATH' ) ) {
	exit;
}
?>
<div class="grafema-main">
    <!-- tabs start -->
	<?php Dashboard\Form::print( GRFM_DASHBOARD . 'forms/settings.php' ); ?>
    <!-- tabs end -->
</div>
