<?php

use Expansa\Builders\Form\Data;

/**
 * Details html tag: button with dropdown menu
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/fields/details.php
 *
 * @var Data $field
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;

if ($field->label === '') {
	return;
}
?>
<details class="details" @click.outside="$el.removeAttribute('open')">
	<summary class="{{ trim('details-summary ' . $field->class) }}">{!! $field->label !!}</summary>
	<div class="details-content">
		@if($field->instruction)
			<div class="details-head">
				{!! $field->instruction !!}
			</div>
		@endif
		{!! $field->content !!}
	</div>
</details>
