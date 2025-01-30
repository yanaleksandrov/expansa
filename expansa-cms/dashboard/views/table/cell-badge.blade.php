<?php

use Expansa\Facades\Safe;
use Expansa\Support\Arr;

/**
 * Badge
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/table/cell-badge.php
 *
 * @package Expansa\Templates
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
    <span class="badge badge--green-lt" x-text="item.<?php echo $prop; ?>"></span>
</div>
