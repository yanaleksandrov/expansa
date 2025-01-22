<?php

use Expansa\Safe;
use Expansa\Support\Arr;

/*
 * Textarea field
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/fields/textarea.php
 *
 * @package Expansa\Templates
 * @since   2025.1
 */
if ( ! defined( 'EX_PATH' ) ) {
	exit;
}

[ $name, $label, $class, $label_class, $reset, $before, $after, $instruction, $tooltip, $copy, $conditions, $attributes, $options ] = Safe::data(
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
		'options'     => 'array',
	]
)->values();

$value = Safe::textarea( $attributes['value'] ?? '' );
$prop  = Safe::prop( $attributes['name'] ?? $name );

$attributes['rows'] ??= 1;

unset( $attributes['value'] );
?>
<div class="<?php echo $class; ?>"<?php echo $conditions ? " x-show=\"{$conditions}\" x-cloak" : ''; ?>>
	<?php if ( $label ) : ?>
		<span class="<?php echo $label_class; ?>"><?php echo $label; ?></span>
	<?php endif; ?>
	<label class="field-item">
		<?php echo $before; ?>
		<textarea<?php echo Arr::toHtmlAtts( $attributes ); ?>><?php echo $value; ?></textarea>
		<?php
		echo $after;
		if ( $copy ) {
			?>
			<i class="ph ph-copy" title="<?php t_attr( 'Copy' ); ?>" @click="$copy(<?php echo $prop; ?>)"></i>
			<?php
		}
		if ( $tooltip ) {
			?>
			<i class="ph ph-info" x-tooltip.click.prevent="'<?php echo $tooltip; ?>'"></i>
			<?php
		}
		?>
	</label>
	<?php if ( $instruction ) : ?>
		<div class="field-instruction"><?php echo $instruction; ?></div>
	<?php endif; ?>
</div>
