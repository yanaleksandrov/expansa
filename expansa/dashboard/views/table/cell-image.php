<?php
use Expansa\Sanitizer;

/**
 * Table image cell
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/table/cells/image.php
 *
 * @package Expansa\Templates
 * @since   2025.1
 */
if ( ! defined( 'EX_PATH' ) ) {
	exit;
}

$class = Sanitizer::class($args['key'] ?? [] );
$prop  = Sanitizer::prop($args['key'] ?? [] );
?>
<div class="<?php echo $class; ?>">
    <span class="avatar avatar--rounded" :style="`background-image: url(${item.<?php echo $prop; ?>})`"></span>
</div>
