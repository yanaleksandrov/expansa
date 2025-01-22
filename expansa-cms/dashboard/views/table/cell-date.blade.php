<?php

use Expansa\Safe;
use Expansa\Support\Arr;

/**
 * Publish date
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/table/cells/date.php
 *
 * @package Expansa\Templates
 * @since   2025.1
 */
if ( ! defined( 'EX_PATH' ) ) {
	exit;
}

[ $prop, $attributes ] = Safe::data(
	$args ?? [],
	[
		'key'        => 'prop',
		'attributes' => 'array',
	]
)->values();
?>
<div<?php echo Arr::toHtmlAtts( $attributes ); ?>>
	Published
	<div x-text="item.<?php echo $prop; ?>"></div>
</div>
