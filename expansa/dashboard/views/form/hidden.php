<?php

use Expansa\Sanitizer;
use Expansa\Support\Arr;

/**
 * Hidden input field
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/fields/hidden.php
 *
 * @package Expansa\Templates
 * @since   2025.1
 */
if ( ! defined( 'EX_PATH' ) ) {
	exit;
}

$attributes = Sanitizer::array( $args['attributes'] ?? [] );
?>
<input<?php echo Arr::toHtmlAtts( $attributes ); ?>>
