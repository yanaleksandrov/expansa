<?php

use Expansa\Safe;
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

[ $name, $label, $class, $labelClass, $reset, $before, $after, $instruction, $tooltip, $copy, $conditions, $attributes, $switcher, $indicator, $generator, $characters ] = Safe::data(
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
		// password
		'switcher'    => 'bool:true',
		'indicator'   => 'bool:false',
		'generator'   => 'bool:false',
		'characters'  => 'array',
	]
)->values();

$prop       = Safe::prop( $attributes['name'] ?? $name );
$attributes = [
	...$attributes,
	'name'          => $name,
	':type'         => "show ? 'password' : 'text'",
	'@input.window' => $generator ? 'data = $password.check(' . $prop . ')' : '',
];
?>
<div class="{{ $class }}" x-data="{show: true, data: {}}">
	<div class="{{ $labelClass }}">
		{!! $label !!}
		@if($generator)
			<div class="ml-auto fw-400 fs-13 t-muted" @click="{{ $prop }} = $password.generate(); $dispatch('input')">{{ t( 'Generate' ) }}</div>
		@endif
	</div>
	<div class="field-item">
		<input<?php echo Arr::toHtmlAtts( $attributes ); ?>>
		@if($switcher)
			<i class="ph" :class="show ? 'ph-eye-closed' : 'ph-eye'" @click="show = $password.switch(show)"></i>
		@endif
		@if($copy)
			<i class="ph ph-copy" title="{{ t( 'Copy' ) }}" x-copy="{{ $prop }}"></i>
		@endif
	</div>
	@if($instruction)
		<div class="field-instruction">{!! $instruction !!}</div>
	@endif
	@if($indicator)
		<div class="dg g-2 gtc-5 mt-2">
			<i class="pt-1" :class="data.progress > <?php echo 100 / 5; ?> ? 'bg-red' : 'bg-muted-lt'"></i>
			<i class="pt-1" :class="data.progress > <?php echo 100 / 5 * 2; ?> ? 'bg-amber' : 'bg-muted-lt'"></i>
			<i class="pt-1" :class="data.progress > <?php echo 100 / 5 * 3; ?> ? 'bg-orange' : 'bg-muted-lt'"></i>
			<i class="pt-1" :class="data.progress > <?php echo 100 / 5 * 4; ?> ? 'bg-green' : 'bg-muted-lt'"></i>
			<i class="pt-1" :class="data.progress === 100 ? 'bg-green' : 'bg-muted-lt'"></i>
		</div>
	@endif
	@if($characters)
		<div class="dg g-2 gtc-2 t-muted fs-13 mt-3 lh-xs">
			<?php
			$messages = [
				'lowercase' => t( '%d lowercase letters' ),
				'uppercase' => t( '%d uppercase letters' ),
				'special'   => t( '%d special characters' ),
				'length'    => t( '%d characters minimum' ),
				'digit'     => t( '%d numbers' ),
			];

            foreach ( $characters as $character => $count ) {
                if ( empty( $character ) || empty( $messages[$character] ) || $count <= 0 ) {
                    continue;
                }
                ?>
				<div class="df aifs g-2" :class="data.{{ $character }} && 't-green'">
					<i class="ph" :class="data.{{ $character }} ? 'ph-check' : 'ph-x'"></i> <span><?php printf( $messages[$character], $count ); ?></span>
				</div>
			<?php } ?>
		</div>
	@endif
</div>
