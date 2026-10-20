<?php

use Expansa\Builders\Form\Data;
use Expansa\Support\Arr;

/**
 * Button group template can be overridden by copying it to themes/yourtheme/dashboard/views/fields/button-group.php
 *
 * @var Data $field
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;

$value = (string) ($field->attributes['value'] ?? '');
?>
<div class="{{ $field->class ?: 'field' }}"{!! Arr::toHtmlAttributes($field->conditions) !!}>
	<div class="field-box">
		@if($field->label)
			<div class="{{ $field->label_class ?: 'field-label' }}">
				{!! $field->label !!}
				@if($field->tooltip)
					<i class="ph ph-info" u-tooltip.click.prevent="'{!! $field->tooltip !!}'"></i>
				@endif
			</div>
		@endif
		@if($field->instruction)
			<div class="field-instruction">{!! $field->instruction !!}</div>
		@endif
	</div>
	<div class="field-buttons">
		@foreach($field->options as $option)
			<label class="field-buttons-item">
				<input{!! Arr::toHtmlAttributes([...$field->attributes, 'type' => 'radio', 'value' => $option->value, 'name' => $field->name, 'u-prop' => $field->prop, 'checked' => $option->value === $value]) !!}>
				{!! $option->content !!}
			</label>
		@endforeach
	</div>
</div>
