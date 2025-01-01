<?php

use Expansa\I18n;
use Expansa\Sanitizer;
use Expansa\Support\Arr;

/*
 * Password field
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/fields/password.php
 *
 * @package Expansa\Templates
 * @since   2025.1
 */
if ( ! defined( 'EX_PATH' ) ) {
	exit;
}

[ $name, $label, $class, $label_class, $reset, $before, $after, $instruction, $tooltip, $copy, $conditions, $attributes, $switcher, $indicator, $generator, $characters ] = ( new Sanitizer(
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
		// password
		'switcher'    => 'bool:true',
		'indicator'   => 'bool:false',
		'generator'   => 'bool:false',
		'characters'  => 'array',
	]
) )->values();

$prop       = Sanitizer::prop( $attributes['name'] ?? $name );
$attributes = [
	...$attributes,
	'name'          => $name,
	':type'         => "show ? 'password' : 'text'",
	'@input.window' => $generator ? 'data = $password.check(' . $prop . ')' : '',
];
?>
<div class="<?php echo $class; ?>" x-data="{show: true, data: {}}">
	<div class="<?php echo $label_class; ?>"><?php
		echo $label;
        if ( $generator ) {
            ?>
			<span class="ml-auto fw-400 fs-13 t-muted" @click="<?php echo $prop; ?> = $password.generate(); $dispatch('input')"><?php I18n::t( 'Generate' ); ?></span>
		<?php } ?>
	</div>
	<div class="field-item">
		<input<?php echo Arr::toHtmlAtts( $attributes ); ?>>
		<?php if ( $switcher ) : ?>
			<i class="ph" :class="show ? 'ph-eye-closed' : 'ph-eye'" @click="show = $password.switch(show)"></i>
			<?php
        endif;
        if ( $copy ) :
            ?>
			<i class="ph ph-copy" title="<?php I18n::t_attr( 'Copy' ); ?>" x-copy="<?php echo $prop; ?>"></i>
		<?php endif; ?>
	</div>
	<?php if ( $instruction ) : ?>
		<div class="field-instruction"><?php echo $instruction; ?></div>
		<?php
	endif;
	if ( $indicator ) :
		?>
		<div class="dg g-2 gtc-5 mt-2">
			<i class="pt-1" :class="data.progress > <?php echo 100 / 5; ?> ? 'bg-red' : 'bg-muted-lt'"></i>
			<i class="pt-1" :class="data.progress > <?php echo 100 / 5 * 2; ?> ? 'bg-amber' : 'bg-muted-lt'"></i>
			<i class="pt-1" :class="data.progress > <?php echo 100 / 5 * 3; ?> ? 'bg-orange' : 'bg-muted-lt'"></i>
			<i class="pt-1" :class="data.progress > <?php echo 100 / 5 * 4; ?> ? 'bg-green' : 'bg-muted-lt'"></i>
			<i class="pt-1" :class="data.progress === 100 ? 'bg-green' : 'bg-muted-lt'"></i>
		</div>
		<?php
	endif;
	if ( ! empty( $characters ) ) :
		?>
		<div class="dg g-2 gtc-2 t-muted fs-13 mt-3 lh-xs">
			<?php
			$messages = [
				'lowercase' => I18n::_t( '%d lowercase letters' ),
				'uppercase' => I18n::_t( '%d uppercase letters' ),
				'special'   => I18n::_t( '%d special characters' ),
				'length'    => I18n::_t( '%d characters minimum' ),
				'digit'     => I18n::_t( '%d numbers' ),
			];

            foreach ( $characters as $character => $count ) {
                if ( empty( $character ) || empty( $messages[$character] ) || $count <= 0 ) {
                    continue;
                }
                ?>
				<div class="df aifs g-2" :class="data.<?php echo $character; ?> && 't-green'">
					<i class="ph" :class="data.<?php echo $character; ?> ? 'ph-check' : 'ph-x'"></i> <span><?php printf( $messages[$character], $count ); ?></span>
				</div>
			<?php } ?>
		</div>
	<?php endif; ?>
</div>
