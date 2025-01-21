<?php

use app\View;

/**
 * Themes list.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/themes.php
 *
 * @package Expansa\Templates
 * @since   2025.1
 */
if ( ! defined( 'EX_PATH' ) ) {
	exit;
}
?>
<div class="expansa-main">
	<?php
	View::print(
		'views/table/header',
		[
			'title' => t( 'Themes' ),
		]
	);

	( new Dashboard\Table( new Dashboard\Tables\Themes() ) )->print();
    ?>
</div>
