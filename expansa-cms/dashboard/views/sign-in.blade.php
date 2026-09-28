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
<main class="mw-360" u-data>
	<a href="{{ url() }}" class="df jcc mb-4" target="_blank">
		<img src="{{ url('dashboard/assets/images/logo-grid.svg') }}" width="212" height="124" alt="Expansa CMS">
	</a>
	<?php echo form('user-sign-in', EX_DASHBOARD . 'forms/user-sign-in.php'); ?>
	<div hidden u-show="$passkey.available">
		<button type="button" class="btn btn--lg btn--outline btn--full mt-3"
			@load="$passkey.autofill(() => $ajax.post('user/passkey-options')).then(credential => credential && $ajax.post('user/passkey-sign-in', {credential, remember: remember ? 1 : 0}))"
			@click="$ajax.post('user/passkey-options').then(({options}) => $passkey.get(options)).then(credential => credential && $ajax.post('user/passkey-sign-in', {credential, remember: remember ? 1 : 0}))">
			<i class="ph ph-fingerprint"></i> {!! t('Sign in with a passkey') !!}
		</button>
	</div>
	<div class="fs-13 t-center t-muted mt-3">
		{!! t("Don't have an account yet? [Sign Up](:signUpLink)", url('sign-up')) !!}
	</div>
</main>
