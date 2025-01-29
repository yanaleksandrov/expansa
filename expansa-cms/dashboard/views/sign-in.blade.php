<?php
/**
 * User sing-in template can be overridden by copying it to themes/yourtheme/dashboard/views/sign-in.php
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
	<?php echo form('user-sign-in', EX_DASHBOARD . 'forms/user-sign-in.php'); ?>
	<div class="fs-14 t-center t-muted mt-3">
		{!! t("Don't have an account yet? [Sign Up](:signUpLink)", url('sign-up')) !!}
	</div>
</div>
