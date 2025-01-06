<?php

use Expansa\Safe;
use Expansa\Support\Arr;

/**
 * Form step
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/fields/step.php
 *
 * @package Expansa\Templates
 * @since   2025.1
 */
if ( ! defined( 'EX_PATH' ) ) {
	exit;
}

[ $attributes, $content, $step ] = Safe::data(
    $args ?? [],
    [
        'attributes' => 'array',
        'content'    => 'trim',
        'step'       => 'absint:1',
    ]
)->values();
?>
<!-- step <?php echo $step; ?> -->
<div <?php echo Arr::toHtmlAtts( $attributes ); ?>>
	<?php echo $content; ?>
</div>

