<?php

use Expansa\Builders\Form\Data;

/**
 * Static informational/warning message block.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/fields/message.php
 *
 * @var Data $field
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;

if ($field->label === '' && $field->instruction === '') {
	return;
}
?>
<div class="{{ $field->class ?: 'card-message' }}">
	@if($field->label)
		<div class="fw-500">{!! $field->label !!}</div>
	@endif
	@if($field->instruction)
		<div class="t-muted fs-13 lh-xs">{!! $field->instruction !!}</div>
	@endif
</div>
