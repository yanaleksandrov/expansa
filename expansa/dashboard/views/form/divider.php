<?php
use Expansa\Sanitizer;

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

$label = Sanitizer::trim( $args['label'] ?? '' );
?>
<div class="card-hr"><?php echo $label; ?></div>
