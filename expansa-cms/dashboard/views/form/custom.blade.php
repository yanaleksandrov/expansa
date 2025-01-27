<?php
/**
 * Custom field markup.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/fields/custom.php
 *
 * @package Expansa\Templates
 * @since   2025.1
 */
if ( ! defined( 'EX_PATH' ) ) {
	exit;
}

$callback = $__data['callback'] ?? null;
if ( is_callable( $callback ) ) {
	ob_start();
	call_user_func( $callback );
	echo ob_get_clean();
}

if (is_string($callback)) {
    echo $callback;
}
