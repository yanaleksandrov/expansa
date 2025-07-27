<?php

use Expansa\Facades\Safe;
use Expansa\Support\Arr;

/**
 * Files uploader template can be overridden by copying it to themes/yourtheme/dashboard/views/fields/confirm-email.php
 *
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;

[ $name, $label, $class, $label_class, $reset, $before, $after, $instruction, $tooltip, $copy, $conditions, $attributes ] = Safe::data(
	$__data ?? [],
	[
		'name'        => 'name',
		'label'       => 'trim',
		'class'       => 'class:field',
		'label_class' => 'class:field-label',
		'reset'       => 'bool:false',
		'before'      => 'trim',
		'after'       => 'trim',
		'instruction' => 'trim',
		'tooltip'     => 'attribute',
		'copy'        => 'bool:false',
		'conditions'  => 'array',
		'attributes'  => 'array',
	]
)->values();
?>
<div class="confirm-email active">
	<i class="ph ph-mailbox"></i>
	<div class="confirm-email-main">
		<div class="confirm-email-top">
			<div class="confirm-email-side">
				<?php if ( $label ) : ?>
				<span class="confirm-email-title"><?php echo $label; ?></span>
				<?php endif; ?>

				<?php if ( $instruction ) : ?>
				<span class="confirm-email-instruction"><?php echo $instruction; ?></span>
				<?php endif; ?>
			</div>
			<div class="confirm-email-code">
				<input type="text" placeholder="0" maxlength="1" <?php echo Arr::toHtmlAtts($attributes); ?>>
				<input type="text" placeholder="0" maxlength="1" <?php echo Arr::toHtmlAtts($attributes); ?>>
				<input type="text" placeholder="0" maxlength="1" <?php echo Arr::toHtmlAtts($attributes); ?>>
				<input type="text" placeholder="0" maxlength="1" <?php echo Arr::toHtmlAtts($attributes); ?>>
			</div>
		</div>
		<div class="confirm-email-bottom">
			<a href=""><?php echo t("Didn't get thу email?"); ?></a>
			<button type="button" class="btn btn--outline"><?php echo t('Verify email'); ?></button>
		</div>
	</div>
</div>
