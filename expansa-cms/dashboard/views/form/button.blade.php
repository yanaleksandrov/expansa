<?php

use Expansa\Safe;

/**
 * Custom field markup.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/fields/custom.php
 *
 * @package Expansa\Templates
 */
if ( ! defined( 'EX_PATH' ) ) {
	exit;
}

[ $name, $label, $class, $labelClass, $reset, $before, $after, $instruction, $tooltip, $copy, $conditions, $attributes ] = Safe::data(
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
    ]
)->values();
?>
<button <?php echo $attributes; ?>>{{ $label }}</button>
