<?php

use Expansa\Builders\Form\Data;
use Expansa\Builders\Form\Values\Option;
use Expansa\Support\Arr;

/**
 * Select field template can be overridden by copying it to themes/yourtheme/dashboard/views/fields/select.php
 *
 * @var Data $field
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;

$attributes = $field->attributes;
$value      = (string) ($attributes['value'] ?? '');

unset($attributes['value']);

// the reset link writes the value through u-prop; u-select does not see that, so it is set there too
$prop  = $attributes['u-prop'] ??= $field->prop;
$json  = json_encode($value, JSON_UNESCAPED_UNICODE);
$reset = "$prop = $json; \$el.closest('.field').querySelector('select')._x_slimSelect?.setSelected($json)";

$toAttributes = static fn (Option $option): array => [
    'value'    => $option->value,
    'selected' => $option->value === $value,
    ...$option->data,
];
?>
<div class="{{ $field->class ?: 'field' }}@if($field->invalid) is-invalid@endif"{!! Arr::toHtmlAttributes($field->conditions) !!}>
	@if($field->label)
		<div class="{{ $field->label_class ?: 'field-label' }}"@if($field->required) data-required="*"@endif>
			{!! $field->label !!}
			@if($field->reset)
				<span class="ml-auto t-red" @click="{{ $reset }}" u-show="{{ $prop }} !== {{ $json }}" hidden>{!! t('Reset') !!}</span>
			@endif
			@if($field->tooltip)
				<i class="ph ph-info" u-tooltip.click.prevent="'{!! $field->tooltip !!}'"></i>
			@endif
		</div>
	@endif
	<label class="field-item">
		@if($field->before)
			<span class="field-before">{!! $field->before !!}</span>
		@endif
		<select{!! Arr::toHtmlAttributes($attributes) !!}>
			@foreach($field->options as $option)
				@if($option->options)
					<optgroup label="{{ $option->content }}">
						@foreach($option->options as $item)
							<option{!! Arr::toHtmlAttributes($toAttributes($item)) !!}>{!! $item->content !!}</option>
						@endforeach
					</optgroup>
				@else
					<option{!! Arr::toHtmlAttributes($toAttributes($option)) !!}>{!! $option->content !!}</option>
				@endif
			@endforeach
		</select>
		@if($field->after)
			<span class="field-after">{!! $field->after !!}</span>
		@endif
	</label>
	@if($field->invalid)
		<div class="field-error">{!! $field->invalid !!}</div>
	@endif
	@if($field->instruction)
		<div class="field-instruction">{!! $field->instruction !!}</div>
	@endif
</div>
