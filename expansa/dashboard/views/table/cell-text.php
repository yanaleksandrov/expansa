<?php
use Expansa\Safe;

/**
 * Table title with actions cell
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/table/cell-text.php
 *
 * @package Expansa\Templates
 * @since   2025.1
 */
if ( ! defined( 'EX_PATH' ) ) {
	exit;
}

$class = Safe::class($args['key'] ?? [] );
$prop  = Safe::prop($args['key'] ?? [] );
$value = Safe::trim($args['value'] ?? '' );
?>
<label class="<?php echo $class; ?>">
	<textarea :name="`items[${i}]`" x-text="item.<?php echo $prop; ?>" rows="1" x-textarea="7"></textarea>
</label>
