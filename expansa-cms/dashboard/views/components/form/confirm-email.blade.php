<?php

use Expansa\Builders\Form\Data;

/**
 * Files uploader template can be overridden by copying it to themes/yourtheme/dashboard/views/fields/confirm-email.php
 *
 * @var Data $field
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;
?>
<div class="confirm-email">
	<i class="ph ph-mailbox"></i>
	<div class="confirm-email-main">
		<div class="confirm-email-top">
			<div class="confirm-email-side">
				@if($field->label)
					<span class="confirm-email-title">{!! $field->label !!}</span>
				@endif

				@if($field->instruction)
					<span class="confirm-email-instruction">{!! $field->instruction !!}</span>
				@endif
			</div>
			<div class="confirm-email-code">
				<input type="text" placeholder="0" maxlength="1"{!! $field->htmlAttributes !!}>
				<input type="text" placeholder="0" maxlength="1"{!! $field->htmlAttributes !!}>
				<input type="text" placeholder="0" maxlength="1"{!! $field->htmlAttributes !!}>
				<input type="text" placeholder="0" maxlength="1"{!! $field->htmlAttributes !!}>
			</div>
		</div>
		<div class="confirm-email-bottom">
			<a href="">{!! t("Didn't get the email?") !!}</a>
			<button type="button" class="btn btn--outline">{!! t('Verify Email') !!}</button>
		</div>
	</div>
</div>
