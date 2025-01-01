<?php
use Grafema\Sanitizer;
use Grafema\View;

/**
 * Table checkbox
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/table/cell-checkbox.php
 *
 * @package Grafema\Templates
 * @since   2025.1
 */
if ( ! defined( 'GRFM_PATH' ) ) {
	exit;
}

$class = Sanitizer::class($args['key'] ?? [] );
$prop  = Sanitizer::prop($args['key'] ?? [] );
?>
<div class="<?php echo $class; ?>">
	<?php
	View::print(
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
