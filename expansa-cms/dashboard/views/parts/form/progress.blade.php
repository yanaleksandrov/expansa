<?php

use Expansa\Builders\Form\Data;

/**
 * Progress bar template can be overridden by copying it to themes/yourtheme/dashboard/views/fields/progress.php
 *
 * @var Data $field
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;

// the value between min and max as a percentage, the value itself without a range
$range   = $field->max - $field->min;
$percent = $range > 0 ? ($field->value - $field->min) / $range * 100 : $field->value;
$percent = (int) round(min(max($percent, 0), 100));
?>
<div class="{{ $field->class ?: 'dg g-1' }}">
	<div class="{{ $field->label_class ?: 'df aic jcsb fw-500 mb-1' }}">
		{!! $field->label !!}
	</div>
	<div class="progress" u-progress.0.{{ $percent }}.{{ $field->speed }}ms></div>
	@if($field->instruction)
		<div class="mt-1 fw-500 lh-xs">{!! $field->instruction !!}</div>
	@endif
</div>
