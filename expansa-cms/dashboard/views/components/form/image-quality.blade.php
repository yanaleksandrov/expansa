<?php

use Expansa\Builders\Form\Data;
use Expansa\Facades\Safe;
use Expansa\Support\Arr;

/**
 * Image quality template can be overridden by copying it to themes/yourtheme/dashboard/views/fields/image-quality.php
 *
 * @var Data $field
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;

// the curve is measured for qualities 40-100
$attributes = $field->attributes;
$min        = max(40, Safe::absint($attributes['min'] ?? 40));
$max        = min(100, Safe::absint($attributes['max'] ?? 100));
$from       = Safe::absint($attributes['data-optimal-from'] ?? 0);
$to         = Safe::absint($attributes['data-optimal-to'] ?? 0);
$range      = $max - $min;
$prop       = $field->prop;

unset($attributes['data-optimal-from'], $attributes['data-optimal-to']);

$attributes = [
    ...$attributes,
    'type'  => 'range',
    'class' => 'quality-chart-range',
    'min'   => $min,
    'max'   => $max,
    'value' => Safe::absint($attributes['value'] ?? 0) ?: $from ?: $max,
];

// file size in % of the size at quality 100, one value per quality from 40: 19.35 + 0.302 × (q - 40) + 62.4 × e^(0.139 × (q - 100)),
// fitted to the average of JPEG and WebP (GD, photos 1920×1080 and 320×240); the curve path draws the same function
$size = "[19.4,19.7,20,20.3,20.6,20.9,21.2,21.5,21.8,22.1,22.4,22.7,23.1,23.4,23.7,24,24.3,24.6,25,25.3,25.6,26,26.3,26.7,27,27.4,27.8,28.1,28.5,28.9,29.4,29.8,30.3,30.8,31.3,31.8,32.4,33.1,33.8,34.5,35.3,36.2,37.1,38.2,39.4,40.7,42.1,43.8,45.6,47.7,50,52.6,55.6,58.9,62.8,67.1,72,77.7,84.1,91.5,100][$prop - 40]";
?>
<div class="{{ $field->class ?: 'field' }}"{!! Arr::toHtmlAttributes($field->conditions) !!}>
	@if($field->label)
		<div class="{{ $field->label_class ?: 'field-label' }}">
			{!! $field->label !!}
			@if($field->tooltip)
				<i class="ph ph-info" u-tooltip.click.prevent="'{!! $field->tooltip !!}'"></i>
			@endif
		</div>
	@endif
	{!! $field->before !!}
	<div class="quality-chart">
		<div class="quality-chart-head">
			@if($from)
				<button type="button" class="btn btn--sm" @click="{{ $prop }} = {{ $from }}">{!! t('Use Optimal (%s)', $from) !!}</button>
			@endif
			<span u-text="{{ $prop }} + ' / {{ $max }}'"></span>
		</div>
		<div class="quality-chart-plot">
			@if($from && $to > $from)
				<span class="quality-chart-optimal" style="left: {{ ($from - $min) / $range * 100 }}%; width: {{ ($to - $from) / $range * 100 }}%"></span>
			@endif
			<svg viewBox="{{ $min }} 0 {{ $range }} 100" preserveAspectRatio="none" aria-hidden="true">
				<path class="quality-chart-curve" d="M40,80.64 C53.33,76.58 66.67,75.9 80,64.7 C84,61.34 88,57.05 92,44.43 C94.67,36.02 97.33,24.07 100,0" vector-effect="non-scaling-stroke"/>
			</svg>
			<input{!! Arr::toHtmlAttributes($attributes) !!}>
			<span class="quality-chart-marker" :style="{ left: ({{ $prop }} - {{ $min }}) / {{ $range }} * 100 + '%', top: 100 - {{ $size }} + '%' }"></span>
			<span class="quality-chart-y" style="top: 0">64kb</span>
			<span class="quality-chart-y" style="top: 50%">32kb</span>
			<span class="quality-chart-y" style="top: 100%">0</span>
			<span class="quality-chart-x" style="left: 0">{{ $min }}</span>
			@if($from && $to > $from)
				<span class="quality-chart-x" style="left: {{ ($from - $min) / $range * 100 }}%">{{ $from }}</span>
				<span class="quality-chart-x" style="left: {{ ($to - $min) / $range * 100 }}%">{{ $to }}</span>
			@endif
			<span class="quality-chart-x" style="left: 100%">{{ $max }}</span>
			<span class="quality-chart-title">{!! t('Quality') !!}</span>
		</div>
		<div class="quality-chart-note">{!! t('Rough estimate of the file size compared with maximum quality:') !!} ~<span u-text="Math.round({{ $size }})"></span>%</div>
		@if($field->options)
			<table class="quality-chart-examples">
				<tr>
					<th>{!! t('Example') !!}</th>
					<th>{!! t('Quality 100') !!}</th>
					<th>{!! t('Selected Quality') !!}</th>
				</tr>
				@foreach($field->options as $option)
					<tr>
						<td>{!! Safe::trim($option['content'] ?? '') !!}</td>
						<td>{!! t('%s KB', Safe::absint($option['size'] ?? 0)) !!}</td>
						<td>~<span u-text="Math.round({{ Safe::absint($option['size'] ?? 0) }} * {{ $size }} / 100)"></span> {!! t('KB') !!}</td>
					</tr>
				@endforeach
			</table>
		@endif
	</div>
	{!! $field->after !!}
	@if($field->instruction)
		<div class="field-instruction">{!! $field->instruction !!}</div>
	@endif
</div>
