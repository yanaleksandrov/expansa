<?php

use Expansa\Safe;
use Expansa\Support\Arr;

/**
 * Text input field
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/fields/text.php
 *
 * @package Expansa\Templates
 * @since   2025.1
 */
if ( ! defined( 'EX_PATH' ) ) {
	exit;
}

[ $name, $label, $class, $labelClass, $reset, $before, $after, $instruction, $tooltip, $copy, $conditions, $attributes ] = Safe::data(
	$__data ?? [],
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
)->values();

$prop = Safe::prop( $attributes['name'] ?? $name );
?>
<div class="<?php echo $class; ?>">
	@if($label)
		<div class="{{ $labelClass }}">{!! $label !!}</div>
	@endif
	<label class="field-item">
		{!! $before !!}
		<input<?php echo Arr::toHtmlAtts( $attributes ); ?>>
		{{$after}}
		@if($copy)
			<i class="ph ph-copy" title="<?php echo t_attr( 'Copy' ); ?>" @click="$copy({{$prop}})"></i>
		@endif
		@if($tooltip)
			<i class="ph ph-info" x-tooltip.click.prevent="'{{$tooltip}}'"></i>
		@endif
	</label>
	@if($instruction)
		<div class="field-instruction">{!! $instruction !!}</div>
	@endif
</div>
