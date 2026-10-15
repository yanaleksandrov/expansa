<?php

use Expansa\Builders\Form\Data;

/**
 * Progress bar template can be overridden by copying it to themes/yourtheme/dashboard/views/fields/progress.php
 *
 * @var Data $field
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;
?>
<div class="{{ $field->class ?: 'dg g-1' }}">
	<div class="{{ $field->label_class ?: 'df aic jcsb fw-500 mb-1' }}">
		{!! $field->label !!}
	</div>
	<div class="progress" u-progress.{{ $field->max }}.{{ $field->min }}.{{ $field->value }}.{{ $field->speed }}ms></div>
	@if($field->instruction)
		<div class="mt-1 fw-500 lh-xs">{!! $field->instruction !!}</div>
	@endif
</div>
