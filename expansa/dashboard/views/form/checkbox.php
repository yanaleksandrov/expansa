<?php

use Expansa\Sanitizer;
use Expansa\Support\Arr;

/**
 * Single and multiple checkboxes.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/fields/checkbox.php
 *
 * @package Expansa\Templates
 * @since   2025.1
 */
if ( ! defined( 'EX_PATH' ) ) {
	exit;
}

[ $name, $label, $class, $label_class, $reset, $before, $after, $instruction, $tooltip, $copy, $conditions, $attributes, $options ] = ( new Sanitizer(
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
		'options'     => 'array',
	]
) )->values();

$prop   = Sanitizer::prop( $attributes['name'] ?? $name );
$render = function( $key = '', $option = [] ) use ( $name, $label, $class, $label_class, $reset, $before, $after, $instruction, $tooltip, $copy, $conditions, $attributes ) {
	$prop = Sanitizer::prop( $key ?: $name );

	[ $label, $icon, $instruction, $checked ] = ( new Sanitizer(
		$option,
		[
			'content'     => 'trim:' . $label,
			'icon'        => 'attribute',
			'description' => 'trim:' . $instruction,
			'checked'     => 'bool:' . strval( $attributes['checked'] ?? false ),
		]
	) )->values();

	ob_start();
	?>
	<label class="field-item">
		<?php if ( $icon ) : ?>
			<span class="field-icon"><i class="<?php echo $icon; ?>"></i></span>
		<?php endif; ?>
		<input class="field-checkbox"<?php echo Arr::toHtmlAtts( [ ...$attributes, 'type' => 'checkbox', 'name' => $key ?: $name, 'x-model.fill' => $prop, 'checked' => $checked ] ); ?>>
		<span class="field-switcher"></span>
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
