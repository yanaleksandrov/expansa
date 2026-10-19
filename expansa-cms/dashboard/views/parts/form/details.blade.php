<?php

use Expansa\Builders\Form\Data;

/**
 * Button with a dropdown menu: the popover is anchored to the button, the browser closes it on a click outside and on Esc.
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/fields/details.php
 *
 * @var Data $field
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;

if ($field->label === '') {
	return;
}

$popover = 'details-' . bin2hex(random_bytes(4));
?>
<div class="details">
	<button class="{{ trim('details-summary ' . $field->class) }}" type="button" popovertarget="{{ $popover }}">{!! $field->label !!}</button>
	<div class="details-content" id="{{ $popover }}" popover>
		@if($field->instruction)
			<div class="details-head">
				{!! $field->instruction !!}
			</div>
		@endif
		{!! $field->content !!}
	</div>
</div>
