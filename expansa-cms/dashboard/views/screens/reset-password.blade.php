<?php
/**
 * Reset user password form.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/screens/reset-password.php
 *
 * @var App\Support\Site $site Settings of the site: name, language, charset, addresses, version; shared with every view.
 *
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;
?>
<main class="mw-360">
	<a href="{{ $site->url }}" class="df jcc mb-4" target="_blank">
		<img src="{{ url('dashboard/assets/images/logo-grid.svg') }}" width="212" height="124" alt="Expansa CMS">
	</a>
	<?php echo form('user-reset-password'); ?>
	<div class="t-center t-muted mt-3">
		{!! t('Remembered your password? [Back to Sign In](:signInLink)', url('sign-in')) !!}
	</div>
</main>
