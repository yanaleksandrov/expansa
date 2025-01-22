<?php
/**
 * User profile page.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/profile.php
 *
 * @package Expansa\Templates
 * @since   2025.1
 */
if ( ! defined( 'EX_PATH' ) ) {
	exit;
}
?>
<div class="expansa-main">
	<?php Dashboard\Form::print( EX_DASHBOARD . 'forms/user-profile.php' ); ?>
</div>
