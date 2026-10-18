<?php

use Expansa\Builders\Form\Data;
use Expansa\Support\Arr;

/**
 * Group of fields: the layout places the label, the columns split the fields, `class` adds to the base classes.
 *
 * @var Data $field
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;
?>
<div class="{{ trim("form-group form-group--$field->layout form-group--cols-$field->columns $field->class") }}"{!! Arr::toHtmlAttributes($field->conditions) !!}>
	@if($field->label)
		<div class="form-group-label">{!! $field->label !!}</div>
	@endif
	<div class="form-group-content">
		{!! $field->content !!}
	</div>
</div>
