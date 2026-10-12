<?php

use Expansa\Builders\Form;
use Expansa\Support\Arr;

/**
 * Input field template can be overridden by copying it to themes/yourtheme/dashboard/views/fields/number.php
 *
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;

extract(Form::data($__data, [
	'class' => 'class:field field--number',
]));
?>
<div class="<?php echo $class; ?>">
	<?php if ( $label ) : ?>
		<div class="<?php echo $label_class; ?>"<?php echo ! empty( $attributes['required'] ) ? ' data-required="*"' : ''; ?>><?php echo $label; ?></div>
	<?php endif; ?>
	<div class="field-item">
		<i class="ph ph-minus" @click="$spin(-1)"></i>
		<input type="number"<?php echo Arr::toHtmlAttributes( $attributes ); ?> @keydown.e.prevent>
		<i class="ph ph-plus" @click="$spin(1)"></i>
	</div>
	<?php if ( $instruction ) : ?>
		<div class="field-instruction"><?php echo $instruction; ?></div>
	<?php endif; ?>
</div>
