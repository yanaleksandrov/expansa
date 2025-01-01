<?php

use Grafema\Sanitizer;
use Grafema\Support\Arr;

/**
 * Hidden input field
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/fields/hidden.php
 *
 * @package Grafema\Templates
 * @since   2025.1
 */
if ( ! defined( 'GRFM_PATH' ) ) {
	exit;
}

$attributes = Sanitizer::array( $args['attributes'] ?? [] );
?>
<input<?php echo Arr::toHtmlAtts( $attributes ); ?>>
