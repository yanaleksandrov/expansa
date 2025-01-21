<?php
use Expansa\Url;

/**
 * Reset user password form.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/reset-password.php
 *
 * @package Expansa\Templates
 * @since   2025.1
 */
if ( ! defined( 'EX_PATH' ) ) {
	exit;
}
?>
<div class="mw-360">
	<a href="<?php echo Url::site(); ?>" class="df jcc mb-4" target="_blank">
        <img src="<?php echo Url::site( 'dashboard/assets/images/logo-decorate.svg' ); ?>" width="212" height="124" alt="Expansa CMS">
	</a>
	<?php Dashboard\Form::print( EX_DASHBOARD . 'forms/user-reset-password.php' ); ?>
	<div class="t-center t-muted mt-3">
		<?php t( 'I remembered the password, [send](:signInLink) me to the sign in page', Url::site( 'dashboard/sign-in' ) ); ?>
	</div>
</div>
