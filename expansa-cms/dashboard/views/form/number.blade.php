<?php

use Expansa\Safe;
use Expansa\Support\Arr;

/**
 * Input field
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/fields/number.php
 *
 * @package Expansa\Templates
 */
if ( ! defined( 'EX_PATH' ) ) {
	exit;
}

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

$prop = Safe::prop( $attributes['name'] ?? $name );
?>
<div class="<?php echo $class; ?>">
	<?php if ( $label ) : ?>
		<div class="<?php echo $label_class; ?>"><?php echo $label; ?></div>
	<?php endif; ?>
	<div class="field-item">
		<i class="ph ph-minus" @click="<?php echo $prop; ?>--"></i>
		<input type="number"<?php echo Arr::toHtmlAtts( $attributes ); ?> @keydown.e.prevent>
		<i class="ph ph-plus" @click="<?php echo $prop; ?>++"></i>
	</div>
	<?php if ( $instruction ) : ?>
		<div class="field-instruction"><?php echo $instruction; ?></div>
	<?php endif; ?>
</div>
