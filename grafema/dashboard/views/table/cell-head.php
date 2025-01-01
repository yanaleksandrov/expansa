<?php
use Grafema\Sanitizer;

/**
 * Table raw text cell
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/table/cells/head.php
 *
 * @package Grafema\Templates
 * @since   2025.1
 */
if ( ! defined( 'GRFM_PATH' ) ) {
	exit;
}

$columns = Sanitizer::array( $args ?? [] );
if ( ! $columns ) {
    return;
}
?>
<div class="table__head">
	<?php
	foreach ( $columns as $column ) :
		[ $key, $cell, $title, $sortable ] = (
	        new Sanitizer(
	            (array) $column,
	            [
	                'key'      => 'class',
	                'cell'     => 'key',
	                'title'    => 'trim',
	                'sortable' => 'bool',
	            ]
	        )
		)->values();
	    ?>
	    <div class="<?php echo trim( sprintf( '%s df aic g-1', $key ) ); ?>"><?php
			$title && print( $title );
			if ( $sortable ) :
				?>
	            <i class="ph ph-sort-ascending"></i>
			<?php endif; ?>
	    </div>
	<?php endforeach; ?>
</div>
