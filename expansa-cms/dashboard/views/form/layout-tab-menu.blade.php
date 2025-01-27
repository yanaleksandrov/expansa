<?php
use Expansa\Safe;

/**
 * Form tab menu.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/fields/tab-menu.php
 *
 * @package Expansa\Templates
 * @since   2025.1
 */
if ( ! defined( 'EX_PATH' ) ) {
	exit;
}

$fields    = Safe::array( $__data['fields'] ?? [] );
$fields    = array_filter( $fields, fn( $field ) => $field['type'] === 'tab' );
$classMenu = array_filter( array_column( $fields, 'class_menu' ), fn ( $field ) => $field )[0] ?? '';

if ( count( $fields ) === 0 ) {
	return;
}
?>
<ul class="<?php echo trim( sprintf( 'tab__nav %s', $classMenu ) ); ?>" x-sticky>
	<?php
	foreach ( $fields as $field ) :
		[ $prop, $label, $icon, $class ] = Safe::data(
			$field,
            [
				'name'         => 'prop:tab',
				'label'        => 'trim',
				'icon'         => 'attribute',
				'class_button' => 'class',
            ]
        )->values();
		?>
		<li class="<?php echo trim( sprintf( 'tab__title %s', $class ) ); ?>" x-bind="tabButton('<?php echo $prop; ?>')">
			<?php $icon && printf( '<i class="%s"></i> ', $icon ); ?>
			<?php echo $label; ?>

		</li>
	<?php endforeach; ?>
</ul>
