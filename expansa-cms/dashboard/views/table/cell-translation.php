<?php

use Expansa\Safe;
use Expansa\Support\Arr;

/**
 * Translation cell.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/table/cell-translation.php
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
<label<?php echo Arr::toHtmlAtts( $attributes ); ?>>
	<textarea :name="`translations[${item.source}]`" x-text="item.<?php echo $prop; ?>" rows="1" x-textarea="7"></textarea>
</label>
