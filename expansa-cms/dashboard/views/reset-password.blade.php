<?php
/**
 * Reset user password form.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/reset-password.php
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
	<?php echo form('user-reset-password', EX_DASHBOARD . 'forms/user-reset-password.php'); ?>
	<div class="t-center t-muted mt-3">
		{!! t('I remembered the password, [send](:signInLink) me to the sign in page', url('dashboard/sign-in')) !!}
	</div>
</div>
