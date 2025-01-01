<?php

use Expansa\I18n;
use Expansa\Sanitizer;
use Expansa\Support\Arr;

/**
 * Text input field
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/fields/text.php
 *
 * @package Expansa\Templates
 * @since   2025.1
 */
if ( ! defined( 'EX_PATH' ) ) {
	exit;
}

[ $name, $label, $class, $label_class, $reset, $before, $after, $instruction, $tooltip, $copy, $conditions, $attributes ] = ( new Sanitizer(
	$args ?? [],
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
) )->values();

$prop = Sanitizer::prop( $attributes['name'] ?? $name );
?>
<div class="<?php echo $class; ?>">
	<?php if ( $label ) : ?>
		<div class="<?php echo $label_class; ?>"><?php echo $label; ?></div>
	<?php endif; ?>
	<label class="field-item">
		<?php echo $before; ?>
		<input<?php echo Arr::toHtmlAtts( $attributes ); ?>>
		<?php
		echo $after;
		if ( $copy ) :
			?>
			<i class="ph ph-copy" title="<?php I18n::t_attr( 'Copy' ); ?>" @click="$copy(<?php echo $prop; ?>)"></i>
		<?php
		endif;
		if ( $tooltip ) :
			?>
			<i class="ph ph-info" x-tooltip.click.prevent="'<?php echo $tooltip; ?>'"></i>
		<?php endif; ?>
	</label>
	<?php if ( $instruction ) : ?>
		<div class="field-instruction"><?php echo $instruction; ?></div>
	<?php endif; ?>
</div>
