<?php

use Expansa\Url;

/**
 * User sign up page.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/sign-up.php
 *
 * @package Expansa\Templates
 */
if ( ! defined( 'EX_PATH' ) ) {
	exit;
}
?>
<div class="mw-360">
	<a href="<?php echo Url::site(); ?>" class="df jcc mb-4" target="_blank">
        <img src="<?php echo Url::site( 'dashboard/assets/images/logo-decorate.svg' ); ?>" width="212" height="124" alt="Expansa CMS">
	</a>
	<?php echo form( EX_DASHBOARD . 'forms/user-sign-up.php' ); ?>
	<div class="fs-14 t-center t-muted mt-3">
		<?php echo t( 'Already have an account? [Sign In](:signInLink)', Url::site( 'dashboard/sign-in' ) ); ?>
	</div>
</div>
