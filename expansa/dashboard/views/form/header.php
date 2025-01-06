<?php
use Expansa\Safe;

/**
 * Form title.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/fields/title.php
 *
 * @package Expansa\Templates
 * @since   2025.1
 */
if ( ! defined( 'EX_PATH' ) ) {
	exit;
}

[ $label, $name, $class, $instruction ] = Safe::data(
    $args ?? [],
    [
        'label'       => 'trim',
        'name'        => 'key',
        'class'       => 'class:t-center',
        'instruction' => 'trim',
    ]
)->values();
?>
<header class="<?php echo $class; ?>">
	<?php if ( $label ) : ?>
		<h4><?php echo $label; ?></h4>
	<?php endif; ?>
	<?php if ( $instruction ) : ?>
		<p class="t-muted"><?php echo $instruction; ?></p>
	<?php endif; ?>
</header>
