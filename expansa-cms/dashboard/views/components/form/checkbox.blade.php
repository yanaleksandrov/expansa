<?php

use Expansa\Builders\Form;
use Expansa\Facades\Safe;
use Expansa\Support\Arr;

/**
 * Single and multiple checkboxes.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/fields/checkbox.php
 *
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;

extract(Form::data($__data, [
	'options' => 'array',
]));

$prop   = Safe::prop( $attributes['name'] ?? $name );
$render = function( $key = '', $option = [] ) use ( $name, $label, $class, $label_class, $reset, $before, $after, $instruction, $tooltip, $copy, $conditions, $attributes ) {
	$prop = Safe::prop( $key ?: $name );

	[ $label, $icon, $instruction, $checked ] = Safe::data(
		$option,
		[
			'content'     => 'trim:' . $label,
			'icon'        => 'attribute',
			'description' => 'trim:' . $instruction,
			'checked'     => 'bool:' . ( $attributes['checked'] ?? false ),
		]
	)->values();

	ob_start();
	?>
	<div class="field-item">
		@if($icon)
			<span class="field-icon"><i class="{{ $icon }}"></i></span>
		@endif
		<input <?php echo Arr::toHtmlAttributes(
			[ ...$attributes,
				'type'    => 'checkbox',
				'name'    => $key ?: $name,
				'u-prop'  => $prop,
				'checked' => $checked
			]
		); ?>>
		<div class="<?php echo $label_class; ?>"<?php echo ! $key && ! empty( $attributes['required'] ) ? ' data-required="*"' : ''; ?>>
			<?php echo $label; ?>
			<?php if ( $instruction ) : ?>
				<span class="field-instruction"><?php echo $instruction; ?></span>
			<?php endif; ?>
		</div>
	</div>
	<?php
	return ob_get_clean();
};

echo '<div class="dg g-2"' . Arr::toHtmlAttributes( $conditions ) . '>';
if ( $options && $label ) {
	echo '<div class="' . $label_class . '">' . $label . '</div>';
}
echo '<div class="' . $class . '">';
if ( $options ) {
	foreach ( $options as $key => $option ) {
		echo $render( $key, $option );
	}
} else {
	echo $render();
}
echo '</div></div>';
