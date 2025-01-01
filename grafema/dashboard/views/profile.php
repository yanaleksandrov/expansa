<?php
/**
 * User profile page.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/profile.php
 *
 * @package Grafema\Templates
 * @since   2025.1
 */
if ( ! defined( 'GRFM_PATH' ) ) {
	exit;
}
?>
<div class="grafema-main">
	<?php Dashboard\Form::print( GRFM_DASHBOARD . 'forms/user-profile.php' ); ?>
</div>
