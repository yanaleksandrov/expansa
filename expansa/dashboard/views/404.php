<?php
use Expansa\I18n;

/**
 * 404 page.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/404.php
 *
 * @package Expansa\Templates
 * @since   2025.1
 */
if ( ! defined( 'EX_PATH' ) ) {
	exit;
}
?>
<div class="expansa-main">
	<div class="df fdc aic jcc t-center t-muted">
		<h1 class="fs-64">404</h1>
		<p><?php I18n::t( 'Oops! Page not found.' ); ?></p>
	</div>
</div>
