<?php

use Expansa\Facades\Safe;

/**
 * Static informational/warning message block.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/fields/message.php
 *
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;

[ $label, $instruction, $class ] = Safe::data(
	$__data ?? [],
	[
		'label'       => 'trim',
		'instruction' => 'html',
		'class'       => 'class:card-message',
	]
)->values();

if (empty($label) && empty($instruction)) {
	return;
}
?>
<div class="<?php echo $class; ?>">
	<?php if ($label) : ?>
		<div class="fw-500"><?php echo $label; ?></div>
	<?php endif; ?>
	<?php if ($instruction) : ?>
		<div class="t-muted fs-13 lh-xs"><?php echo $instruction; ?></div>
	<?php endif; ?>
</div>
