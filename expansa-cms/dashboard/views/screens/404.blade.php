<?php
/**
 * 404-page template can be overridden by copying it to themes/yourtheme/dashboard/views/screens/404.php
 *
 * @var string $message Reason, e.g. "User not found."
 *
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;
?>
<div class="df fdc aic jcc t-center t-muted">
	<h1 class="fs-64">404</h1>
	<p>{{ ($message ?? '') ?: t('Page not found') }}</p>
</div>
