<?php

use Grafema\Sanitizer;
use Grafema\Support\Arr;

/**
 * Publish date
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/table/cells/date.php
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
	Published
	<div x-text="item.<?php echo $prop; ?>"></div>
</div>
