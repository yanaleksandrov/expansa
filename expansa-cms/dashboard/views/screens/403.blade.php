<?php
/**
 * 403-page template can be overridden by copying it to themes/yourtheme/dashboard/views/screens/403.php
 *
 * @var string $message Reason, e.g. "User not found."
 *
 * @package Expansa\Templates
 */
if ( ! defined( 'EX_PATH' ) ) {
	exit;
}
?>
<div class="df fdc aic jcc t-center t-muted">
	<h1 class="fs-64">403</h1>
	<p>{{ ($message ?? '') ?: t('You are not allowed to open this page.') }}</p>
</div>
