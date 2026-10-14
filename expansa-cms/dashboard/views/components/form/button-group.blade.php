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

// an option is a button: `content`, `icon`, `class` and `attributes` with its action
$buttons = [];
foreach ($field->options as $option) {
    $buttons[] = [
        'content'    => Safe::trim($option['content'] ?? ''),
        'icon'       => Safe::attribute($option['icon'] ?? ''),
        'attributes' => [
            'type'  => 'button',
            'class' => Safe::attribute($option['class'] ?? 'btn btn--outline'),
            ...Safe::array($option['attributes'] ?? []),
        ],
    ];
}
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
	<div class="df fww g-2">
		@foreach($buttons as $button)
			<button{!! Arr::toHtmlAttributes($button['attributes']) !!}>
				@if($button['icon'])
					<i class="{!! $button['icon'] !!}"></i>
				@endif
				{!! $button['content'] !!}
			</button>
		@endforeach
	</div>
	{!! $field->after !!}
	@if($field->instruction)
		<div class="field-instruction">{!! $field->instruction !!}</div>
	@endif
</div>
