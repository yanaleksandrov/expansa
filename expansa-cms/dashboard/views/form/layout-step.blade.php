<?php

use Expansa\Safe;
use Expansa\Support\Arr;

/**
 * Form step
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/fields/step.php
 *
 * @package Expansa\Templates
 */
if ( ! defined( 'EX_PATH' ) ) {
	exit;
}

[ $attributes, $content, $step ] = Safe::data(
    $__data ?? [],
    [
        'attributes' => 'attributes',
        'content'    => 'trim',
        'step'       => 'absint:1',
    ]
)->values();
?>
<!-- step <?php echo $step; ?> -->
<div{!! $attributes !!}>
    {!! $content !!}
</div>

