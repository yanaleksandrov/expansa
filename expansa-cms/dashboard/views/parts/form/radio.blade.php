<?php

use Expansa\Builders\Form\Data;
use Expansa\Facades\Safe;
use Expansa\Support\Arr;

/**
 * Radio buttons template can be overridden by copying it to themes/yourtheme/dashboard/views/fields/radio.php
 *
 * @var Data $field
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;

// a single radio is an option without a key
$radios = [];
foreach ($field->options ?: ['' => []] as $key => $option) {
    $radios[] = [
        'label'       => Safe::trim($option['content'] ?? '') ?: $field->label,
        'icon'        => Safe::attribute($option['icon'] ?? ''),
        'instruction' => Safe::trim($option['description'] ?? ''),
        'required'    => ! $key && $field->required,
        'attributes'  => [
            ...$field->attributes,
            'type'    => 'radio',
            'value'   => Safe::attribute($key ?: $field->name),
            'name'    => $field->name,
            'u-prop'  => Safe::prop($field->name),
            'checked' => Safe::bool($option['checked'] ?? $field->attributes['checked'] ?? false),
        ],
    ];
}
?>
<div class="dg g-2"{!! Arr::toHtmlAttributes($field->conditions) !!}>
	@if($field->options && $field->label)
		<div class="{{ $field->label_class ?: 'field-label' }}">{!! $field->label !!}</div>
	@endif
	<div class="{{ $field->class ?: 'field' }}">
		@foreach($radios as $radio)
			<label class="field-item">
				@if($radio['icon'])
					<span class="field-icon"><i class="{!! $radio['icon'] !!}"></i></span>
				@endif
				<input{!! Arr::toHtmlAttributes($radio['attributes']) !!}>
				<span class="{{ $field->label_class ?: 'field-label' }}"@if($radio['required']) data-required="*"@endif>
					{!! $radio['label'] !!}
					@if($radio['instruction'])
						<span class="field-instruction">{!! $radio['instruction'] !!}</span>
					@endif
				</span>
			</label>
		@endforeach
	</div>
</div>
