<?php

use Expansa\Safe;
use Expansa\Support\Arr;

/*
 * Radio buttons
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/fields/radio.php
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

$render = function( $key = '', $option = [] ) use ( $name, $label, $class, $label_class, $reset, $before, $after, $instruction, $tooltip, $copy, $conditions, $attributes ) {
	$prop  = Safe::prop( $name );
	$value = Safe::attribute( $key ?: $name );

	[ $label, $icon, $instruction, $checked ] = Safe::data(
		$option,
		[
			'content'     => 'trim:' . $label,
			'icon'        => 'attribute',
			'description' => 'trim',
			'checked'     => 'bool:' . strval( $attributes['checked'] ?? false ),
		]
	)->values();

	ob_start();
	?>
	<label class="field-item">
		<input<?php echo Arr::toHtmlAtts( [ ...$attributes, 'type' => 'radio', 'value' => $value, 'name' => $name, 'x-model.fill' => $prop, 'checked' => $checked ] ); ?>>
		<?php if ( $icon ) : ?>
			<span class="field-icon"><i class="<?php echo $icon; ?>"></i></span>
		<?php endif; ?>
		<span class="<?php echo $label_class; ?>">
		<?php echo $label; ?>
			<?php if ( $instruction ) : ?>
				<span class="field-instruction"><?php echo $instruction; ?></span>
			<?php endif; ?>
		</span>
	</label>
	<?php
	return ob_get_clean();
};

echo '<div class="' . $class . '">';
if ( $options ) {
	foreach ( $options as $key => $option ) {
		echo $render( $key, $option );
	}
} else {
	echo $render();
}
echo '</div>';
