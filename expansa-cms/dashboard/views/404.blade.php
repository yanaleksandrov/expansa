<?php
/**
 * 404-page template can be overridden by copying it to themes/yourtheme/dashboard/views/404.php
 *
 * @package Expansa\Templates
 */
if ( ! defined( 'EX_PATH' ) ) {
	exit;
}
?>
<div class="expansa-main">
	<div class="df fdc aic jcc t-center t-muted">
		<h1 class="fs-64">404</h1>
		<p>{{ t('Oops! Page not found.') }}</p>
	</div>
</div>
