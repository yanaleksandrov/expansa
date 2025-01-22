<?php
use Expansa\Safe;

/**
 * Form title.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/fields/title.php
 *
 * @package Expansa\Templates
 * @since   2025.1
 */
if ( ! defined( 'EX_PATH' ) ) {
	exit;
}

[ $label, $name, $class, $instruction ] = Safe::data(
	$__data ?? [],
    [
        'label'       => 'trim',
        'name'        => 'key',
        'class'       => 'class:t-center',
        'instruction' => 'trim',
    ]
)->values();
?>
<header class="{{ $class }}">
	@if($label)
		<h4>{!! $label !!}</h4>
	@endif
	@if($instruction)
		<p class="t-muted">{!! $instruction !!}</p>
	@endif
</header>
