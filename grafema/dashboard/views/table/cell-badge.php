<?php

use Grafema\Sanitizer;
use Grafema\Support\Arr;

/**
 * Badge
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/table/cell-badge.php
 *
 * @package Grafema\Templates
 * @since   2025.1
 */
if ( ! defined( 'GRFM_PATH' ) ) {
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
<div<?php echo Arr::toHtmlAtts( $attributes ); ?>>
    <span class="badge badge--green-lt" x-text="item.<?php echo $prop; ?>"></span>
</div>
