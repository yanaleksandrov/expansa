<?php

use Expansa\Sanitizer;
use Expansa\Support\Arr;
use Expansa\View;

/**
 * Table row content.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/table/row.php
 *
 * @package Expansa\Templates
 * @since   2025.1
 */
if ( ! defined( 'EX_PATH' ) ) {
	exit;
}

if ( ! is_array( $data ) || empty( $row ) || empty( $columns ) ) {
	return;
}

[ $tag, $view, $attributes ] = (
	new Sanitizer(
		(array) $row,
		[
			'tag'        => 'tag',
			'view'       => 'trim',
			'attributes' => 'array',
		]
	)
)->values();

$tag && printf( '<%s>', trim( sprintf( '%s %s', $tag, Arr::toHtmlAtts( $attributes ) ) ) );
foreach ( $columns as $column ) {
	View::print( $column->view, [ ...(array) $column, ...$data ] );
}
$tag && printf( '</%s>' . PHP_EOL, $tag );