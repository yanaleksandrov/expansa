<?php

use Expansa\View;
use Expansa\Safe;

/**
 * Table checkbox
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/table/cell-checkbox.php
 *
 * @package Expansa\Templates
 * @since   2025.1
 */
if ( ! defined( 'EX_PATH' ) ) {
	exit;
}

$class = Safe::class($__data['key'] ?? [] );
$prop  = Safe::prop($__data['key'] ?? [] );
?>
<div class="<?php echo $class; ?>">
	<?php
	echo view(
		'views/form/checkbox',
		[
			'type'        => 'checkbox',
			'name'        => 'uid',
			'label'       => '',
			'class'       => '',
			'label_class' => '',
			'reset'       => 0,
			'before'      => '',
			'after'       => '',
			'instruction' => '',
			'tooltip'     => '',
			'copy'        => 0,
			'validator'   => '',
			'conditions'  => [],
			'attributes'  => [
				':checked' => "item.$prop === true",
				'@change'  => '$ajax("plugin/deactivate")',
			],
		]
	);
	?>
</div>
