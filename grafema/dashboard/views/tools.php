<?php
/**
 * Grafema dashboard tools.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/tools.php
 *
 * @package Grafema\Templates
 * @since   2025.1
 */
if ( ! defined( 'GRFM_PATH' ) ) {
	exit;
}
?>
<div class="grafema-main p-7 bg-gray-lt">
	<?php Dashboard\Form::print( GRFM_DASHBOARD . 'forms/tools-list.php' ); ?>
</div>
