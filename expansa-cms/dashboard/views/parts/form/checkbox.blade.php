<?php

use Expansa\Builders\Form\Data;
use Expansa\Builders\Form\Values\Option;
use Expansa\Support\Arr;

/**
 * Single and multiple checkboxes.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/fields/checkbox.php
 *
 * @var Data $field
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;

// a single checkbox is an option without a key
$options = $field->options ?: [new Option()];
?>
<div class="dg g-2"{!! Arr::toHtmlAttributes($field->conditions) !!}>
	@if($field->options && $field->label)
		<div class="{{ $field->label_class ?: 'field-label' }}">{!! $field->label !!}</div>
	@endif
	<div class="{{ $field->class ?: 'field' }}">
		@foreach($options as $option)
			<label class="field-item">
				@if($option->icon)
					<span class="field-icon"><i class="{{ $option->icon }}"></i></span>
				@endif
				<input{!! Arr::toHtmlAttributes([...$field->attributes, 'type' => 'checkbox', 'name' => $option->value ?: $field->name, 'u-prop' => $option->value ? $option->prop : $field->prop, 'checked' => $option->checked ?? $field->checked]) !!}>
				<span class="{{ $field->label_class ?: 'field-label' }}"@if(! $option->value && $field->required) data-required="*"@endif>
					{!! $option->content ?: $field->label !!}
					@if($option->description ?: $field->instruction)
						<span class="field-instruction">{!! $option->description ?: $field->instruction !!}</span>
					@endif
				</span>
			</label>
		@endforeach
	</div>
</div>
