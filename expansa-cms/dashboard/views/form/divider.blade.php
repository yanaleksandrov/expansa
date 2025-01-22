<?php
use Expansa\Safe;

/**
 * Form divider
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/fields/divider.php
 *
 * @package Expansa\Templates
 * @since   2025.1
 */
if ( ! defined( 'EX_PATH' ) ) {
	exit;
}

$label = Safe::trim( $__data['label'] ?? '' );
?>
<div class="card-hr">{!! $label !!}</div>
