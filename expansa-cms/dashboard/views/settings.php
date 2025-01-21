<?php
/**
 * Expansa dashboard settings page.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/settings.php
 *
 * @package Expansa\Templates
 * @since   2025.1
 */
if ( ! defined( 'EX_PATH' ) ) {
	exit;
}
?>
<div class="expansa-main">
    <!-- tabs start -->
	<?php Dashboard\Form::print( EX_DASHBOARD . 'forms/settings.php' ); ?>
    <!-- tabs end -->
</div>
