<?php

use Expansa\Sanitizer;
use Expansa\Support\Arr;

/**
 * Table raw text cell
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/table/cells/raw.php
 *
 * @package Expansa\Templates
 * @since   2025.1
 */
if ( ! defined( 'EX_PATH' ) ) {
	exit;
}

[ $prop, $attributes ] = (
    new Sanitizer(
		$args ?? [],
        [
	        'key'        => 'prop',
	        'attributes' => 'array',
        ]
    )
)->values();
?>
<div<?php echo Arr::toHtmlAtts( $attributes ); ?> x-text="item.<?php echo $prop; ?>"></div>
