<?php
/**
 * User sign up template can be overridden by copying it to themes/yourtheme/dashboard/views/sign-up.php
 *
 * @package Expansa\Templates
 */
if ( ! defined( 'EX_PATH' ) ) {
	exit;
}
?>
<div class="mw-360">
	<a href="{{ url() }}" class="df jcc mb-4" target="_blank">
		<img src="{{ url('dashboard/assets/images/logo-decorate.svg') }}" width="212" height="124" alt="Expansa CMS">
	</a>
	<?php echo form('user-sign-up', EX_DASHBOARD . 'forms/user-sign-up.php'); ?>
	<div class="fs-14 t-center t-muted mt-3">
		{!! t('Already have an account? [Sign In](:signInLink)', url('sign-in')) !!}
	</div>
</div>
