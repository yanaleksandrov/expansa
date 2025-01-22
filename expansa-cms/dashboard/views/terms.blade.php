<?php

use Dashboard\Form;

/**
 * Terms editor.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/terms.php
 *
 * @package Expansa\Templates
 * @since   2025.1
 */
if ( ! defined( 'EX_PATH' ) ) {
	exit;
}
?>
<div class="expansa-main">
	<div class="terms">
		<div class="terms-side">
			<?php Form::print( EX_DASHBOARD . 'forms/terms-editor.php' ); ?>
		</div>
		<div class="terms-main">
			<?php ( new Dashboard\Table( new Dashboard\Tables\Terms() ) )->print(); ?>
		</div>
	</div>
</div>
