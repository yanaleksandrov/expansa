<?php

use Expansa\Builders\Form\Data;
use Expansa\Facades\Safe;
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
$checkboxes = [];
foreach ($field->options ?: ['' => []] as $key => $option) {
    $name = $key ?: $field->name;

    $checkboxes[] = [
        'label'       => Safe::trim($option['content'] ?? '') ?: $field->label,
        'icon'        => Safe::attribute($option['icon'] ?? ''),
        'instruction' => Safe::trim($option['description'] ?? '') ?: $field->instruction,
        'required'    => ! $key && $field->required,
        'attributes'  => [
            ...$field->attributes,
            'type'    => 'checkbox',
            'name'    => $name,
            'u-prop'  => Safe::prop($name),
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
		@foreach($checkboxes as $checkbox)
			<div class="field-item">
				@if($checkbox['icon'])
					<span class="field-icon"><i class="{!! $checkbox['icon'] !!}"></i></span>
				@endif
				<input{!! Arr::toHtmlAttributes($checkbox['attributes']) !!}>
				<div class="{{ $field->label_class ?: 'field-label' }}"@if($checkbox['required']) data-required="*"@endif>
					{!! $checkbox['label'] !!}
					@if($checkbox['instruction'])
						<span class="field-instruction">{!! $checkbox['instruction'] !!}</span>
					@endif
				</div>
			</div>
		@endforeach
	</div>
</div>
