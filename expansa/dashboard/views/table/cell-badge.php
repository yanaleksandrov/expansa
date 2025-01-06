<?php

use Expansa\Safe;
use Expansa\Support\Arr;

/**
 * Badge
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/table/cell-badge.php
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
    <span class="badge badge--green-lt" x-text="item.<?php echo $prop; ?>"></span>
</div>
