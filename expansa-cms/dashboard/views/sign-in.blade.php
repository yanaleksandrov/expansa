<?php

use Expansa\Url;

/**
 * User sing in page.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/sign-in.php
 *
 * @package Expansa\Templates
 */
if ( ! defined( 'EX_PATH' ) ) {
	exit;
}
?>
<div class="mw-360">
	<a href="<?php echo url(); ?>" class="df jcc mb-4" target="_blank">
        <img src="<?php echo url( 'dashboard/assets/images/logo-decorate.svg' ); ?>" width="212" height="124" alt="Expansa CMS">
	</a>
	<?php echo form( EX_DASHBOARD . 'forms/user-sign-in.php' ); ?>
	<div class="fs-14 t-center t-muted mt-3">
		<?php echo t( "Don't have an account yet? [Sign Up](:signUpLink)", url( 'dashboard/sign-up' ) ); ?>
	</div>
</div>
