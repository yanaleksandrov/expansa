<?php

use Expansa\I18n;
use Expansa\Safe;
use Expansa\Support\Arr;

/**
 * Select field
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/fields/select.php
 *
 * @package Expansa\Templates
 * @since   2025.1
 */
if ( ! defined( 'EX_PATH' ) ) {
	exit;
}

[ $name, $label, $class, $label_class, $reset, $before, $after, $instruction, $tooltip, $copy, $conditions, $attributes, $options ] = Safe::data(
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
)->values();

$value = Safe::attribute( $attributes['value'] ?? '' );
$prop  = Safe::prop( $attributes['name'] ?? $name );

unset( $attributes['value'] );
?>
<div class="<?php echo $class; ?>"<?php echo Arr::toHtmlAtts( $conditions ); ?>>
	<?php if ( $label ) : ?>
		<div class="<?php echo $label_class; ?>"><?php
			echo $label;
			if ( $reset ) :
				$click = sprintf( "%s = '%s'", $prop, $value );
				$show  = sprintf( "%s !== '%s'", $prop, $value );
				?>
				<span class="ml-auto t-red" @click="<?php echo $click; ?>" x-show="<?php echo $show; ?>" x-cloak><?php I18n::t( 'Reset' ); ?></span>
				<?php
			endif;
			$tooltip && printf( ' <i class="ph ph-info" x-tooltip.click.prevent="\'%s\'"></i>', $tooltip );
			?>
		</div>
	<?php endif; ?>
	<label class="field-item">
		<?php echo $before; ?>
		<select<?php echo Arr::toHtmlAtts( $attributes ); ?>>
			<?php
			$get_attributes = function ( $key, $value, $option ) {
				$attributes = [
					'value'    => trim( (string) $key ),
					'selected' => $key === $value,
				];

				if ( is_array( $option ) ) {
					unset( $option['content'] );

					foreach ( $option as $attribute => $value ) {
						$attributes['data-' . $attribute] = trim( (string) $value );
					}
				}

				return $attributes;
			};

			foreach ( $options as $option_key => $option ) {
				$optgroup_options = $option['options'] ?? [];
				if ( $optgroup_options ) {
					$label = Safe::attribute( $option['label'] ?? '' );
					?>
					<optgroup label="<?php echo $label; ?>">
						<?php
						foreach ( $optgroup_options as $i => $optgroup_option ) {
							$content = trim( is_scalar( $optgroup_option ) ? $optgroup_option : strval( $optgroup_option['content'] ?? '' ) );
							$atts    = $get_attributes( $i, $value, $optgroup_option );
							?>
							<option<?php echo Arr::toHtmlAtts( $atts ); ?>><?php echo $content; ?></option>
							<?php
						}
						?>
					</optgroup>
					<?php
				} else {
					$content = trim( is_scalar( $option ) ? $option : strval( $option['content'] ?? '' ) );
					$atts    = $get_attributes( $option_key, $value, $option );
					?>
					<option<?php echo Arr::toHtmlAtts( $atts ); ?>><?php echo $content; ?></option>
					<?php
				}
			}
			?>
		</select>
		<?php echo $after; ?>
	</label>
	<?php if ( $instruction ) : ?>
		<div class="field-instruction"><?php echo $instruction; ?></div>
	<?php endif; ?>
</div>
