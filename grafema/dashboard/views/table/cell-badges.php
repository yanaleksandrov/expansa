<?php

use Grafema\Sanitizer;
use Grafema\Support\Arr;

/**
 * Comma-separated list of links
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/table/cells/links.php
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
	<template x-for="(badge, index) in item.<?php echo $prop; ?>">
		<span class="badge badge--lg" :class="badge.class" x-text="badge.title"></span>
	</template>
</div>
