<?php

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

$prop   = Safe::prop( $attributes['name'] ?? $name );
$render = function( $key = '', $option = [] ) use ( $name, $label, $class, $label_class, $reset, $before, $after, $instruction, $tooltip, $copy, $conditions, $attributes ) {
	$prop = Safe::prop( $key ?: $name );

	[ $label, $icon, $instruction, $checked ] = Safe::data(
		$option,
		[
			'content'     => 'trim:' . $label,
			'icon'        => 'attribute',
			'description' => 'trim:' . $instruction,
			'checked'     => 'bool:' . strval( $attributes['checked'] ?? false ),
		]
	)->values();

	ob_start();
	?>
	<div class="field-item">
		@if($icon)
			<span class="field-icon"><i class="{{ $icon }}"></i></span>
		@endif
		<input class="field-checkbox"<?php echo Arr::toHtmlAtts( [ ...$attributes, 'type' => 'checkbox', 'name' => $key ?: $name, 'u-prop' => $prop, 'checked' => $checked ] ); ?>>
		<div class="<?php echo $label_class; ?>">
			<?php echo $label; ?>
			<?php if ( $instruction ) : ?>
				<span class="field-instruction"><?php echo $instruction; ?></span>
			<?php endif; ?>
		</div>
	</div>
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
