<?php
/**
 * User sign up template can be overridden by copying it to themes/yourtheme/dashboard/views/screens/sign-up.php
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
	<?php echo form('user-sign-up'); ?>
	<div class="fs-13 t-center t-muted mt-3">
		{!! t('Already have an account? [Sign In](:signInLink)', url('sign-in')) !!}
	</div>
</main>
