<?php

use Expansa\Builders\Form\Data;
use Expansa\Facades\Safe;
use Expansa\Support\Arr;

/**
 * Button group template can be overridden by copying it to themes/yourtheme/dashboard/views/fields/button-group.php
 *
 * @var Data $field
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;

// an option is `value => label`, the radio of the current value is checked
$radios = [];
foreach ($field->options as $value => $label) {
    $radios[] = [
        'label'      => Safe::trim($label),
        'attributes' => [
            ...$field->attributes,
            'type'    => 'radio',
            'value'   => Safe::attribute($value),
            'name'    => $field->name,
            'u-prop'  => Safe::prop($field->name),
            'checked' => (string) $value === (string) ($field->attributes['value'] ?? ''),
        ],
    ];
}
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
		@foreach($radios as $radio)
			<label class="field-buttons-item">
				<input{!! Arr::toHtmlAttributes($radio['attributes']) !!}>
				{!! $radio['label'] !!}
			</label>
		@endforeach
	</div>
</div>
