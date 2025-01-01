<?php

use Grafema\Sanitizer;
use Grafema\Support\Arr;

/**
 * Submit button
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/fields/submit.php
 *
 * @package Grafema\Templates
 * @since   2025.1
 */
if ( ! defined( 'GRFM_PATH' ) ) {
	exit;
}

[ $name, $label, $class, $label_class, $reset, $before, $after, $instruction, $tooltip, $copy, $conditions, $attributes ] = ( new Sanitizer(
	$args ?? [],
	[
		'name'        => 'name',
		'label'       => 'trim',
		'class'       => 'class:field',
		'label_class' => 'class:field-label',
		'reset'       => 'bool:false',
		'before'      => 'trim',
		'after'       => 'trim',
		'instruction' => 'trim',
		'tooltip'     => 'attribute',
		'copy'        => 'bool:false',
		'conditions'  => 'array',
		'attributes'  => 'array',
	]
) )->values();

$attributes['type'] ??= 'submit';
?>
<div class="<?php echo $class; ?>"<?php echo Arr::toHtmlAtts( $conditions ); ?>>
	<button<?php echo Arr::toHtmlAtts( $attributes ); ?>><?php echo $label; ?></button>
</div>
