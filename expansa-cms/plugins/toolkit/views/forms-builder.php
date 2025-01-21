<?php
/**
 * Fields builder
 *
 * This template can be overridden by copying it to themes/yourtheme/toolkit/templates/forms-builder.php
 *
 * @package Expansa\Templates
 * @since   2025.1
 */
if ( ! defined( 'EX_PATH' ) ) {
	exit;
}
?>
<div class="expansa-main p-7">
	<div class="df aic mb-8 sm:mb-5">
		<div class="mr-2">
			<h4>Fields Groups</h4>
			<div class="t-muted fs-12 mw-600">managing custom fields</div>
		</div>
	</div>
	<?php Dashboard\Form::print( EX_DASHBOARD . 'forms/fields-builder.php' ); ?>
</div>
