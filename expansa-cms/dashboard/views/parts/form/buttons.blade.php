<?php

use Expansa\Builders\Form\Data;
use Expansa\Support\Arr;

/**
 * Buttons template can be overridden by copying it to themes/yourtheme/dashboard/views/fields/buttons.php
 *
 * @var Data $field
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;
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
		@foreach($field->options as $option)
			<button{!! Arr::toHtmlAttributes(['type' => 'button', 'class' => $option->class ?: 'btn btn--outline', ...$option->attributes]) !!}>
				@if($option->icon)
					<i class="{{ $option->icon }}"></i>
				@endif
				{!! $option->content !!}
			</button>
		@endforeach
	</div>
	{!! $field->after !!}
	@if($field->instruction)
		<div class="field-instruction">{!! $field->instruction !!}</div>
	@endif
</div>
