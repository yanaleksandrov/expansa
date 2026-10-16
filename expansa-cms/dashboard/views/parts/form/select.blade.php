<?php

use Expansa\Builders\Form\Data;
use Expansa\Facades\Safe;
use Expansa\Support\Arr;

/**
 * Select field template can be overridden by copying it to themes/yourtheme/dashboard/views/fields/select.php
 *
 * @var Data $field
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;

$attributes = $field->attributes;
$value      = Safe::attribute($attributes['value'] ?? '');

unset($attributes['value']);

// the reset link writes the value through u-prop; u-select does not see that, so it is set there too
$prop  = $attributes['u-prop'] ??= $field->prop;
$reset = "$prop = '$value'; \$el.closest('.field').querySelector('select')._x_slimSelect?.setSelected('$value')";

// an option is a label or an array with `content` and data attributes
$toOption = function (int|string $key, mixed $option) use ($value): array {
    $attributes = [
        'value'    => trim((string) $key),
        'selected' => (string) $key === $value,
    ];

    if (is_array($option)) {
        foreach ($option as $attribute => $data) {
            if ($attribute !== 'content') {
                $attributes["data-$attribute"] = trim((string) $data);
            }
        }
    }

    return [
        'content'    => trim(is_scalar($option) ? (string) $option : (string) ($option['content'] ?? '')),
        'attributes' => $attributes,
    ];
};

// an option with `options` is a group
$choices = [];
foreach ($field->options as $key => $option) {
    $choices[] = empty($option['options']) ? $toOption($key, $option) : [
        'label'   => Safe::attribute($option['label'] ?? ''),
        'options' => array_map($toOption, array_keys($option['options']), $option['options']),
    ];
}
?>
<div class="{{ $field->class ?: 'field' }}@if($field->invalid) is-invalid@endif"{!! Arr::toHtmlAttributes($field->conditions) !!}>
	@if($field->label)
		<div class="{{ $field->label_class ?: 'field-label' }}"@if($field->required) data-required="*"@endif>
			{!! $field->label !!}
			@if($field->reset)
				<span class="ml-auto t-red" @click="{{ $reset }}" u-show="{{ $prop }} !== '{!! $value !!}'" hidden>{!! t('Reset') !!}</span>
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
			@foreach($choices as $choice)
				@if(isset($choice['options']))
					<optgroup label="{!! $choice['label'] !!}">
						@foreach($choice['options'] as $option)
							<option{!! Arr::toHtmlAttributes($option['attributes']) !!}>{!! $option['content'] !!}</option>
						@endforeach
					</optgroup>
				@else
					<option{!! Arr::toHtmlAttributes($choice['attributes']) !!}>{!! $choice['content'] !!}</option>
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
