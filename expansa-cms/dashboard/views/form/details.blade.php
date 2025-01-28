<?php
use Expansa\Safe;

/**
 * Details html tag: button with dropdown menu
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/fields/details.php
 *
 * @package Expansa\Templates
 */
if ( ! defined( 'EX_PATH' ) ) {
	exit;
}

[ $label, $instruction, $class, $content ] = Safe::data(
	$__data ?? [],
	[
		'label'       => 'trim',
		'instruction' => 'html',
		'class'       => 'class',
		'content'     => 'trim',
	]
)->values();

if ( empty( $label ) ) {
	return;
}
?>
<details class="details" @click.outside="$el.removeAttribute('open')">
	<summary class="<?php echo trim( 'details-summary ' . $class ); ?>"><?php echo $label; ?></summary>
	<div class="details-content">
		<?php if ( $instruction ) : ?>
			<div class="details-head">
				<?php echo $instruction; ?>
			</div>
		<?php endif; ?>
		<?php $content && print( $content ); ?>
	</div>
</details>
