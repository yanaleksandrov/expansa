<?php

use Expansa\Safe;
use Expansa\Support\Arr;

/**
 * Table raw text cell
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/table/cells/text.php
 *
 * @package Expansa\Templates
 * @since   2025.1
 */
if ( ! defined( 'EX_PATH' ) ) {
	exit;
}

[ $prop, $attributes ] = Safe::data(
    $__data ?? [],
	[
		'key'        => 'prop',
		'attributes' => 'array',
	]
)->values();
?>
<div<?php echo Arr::toHtmlAtts( $attributes ); ?>>
	<input type="checkbox" :name="`items[${i}]`" :value="item.<?php echo $prop; ?>" x-bind="switcher" />
</div>
