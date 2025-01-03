<?php

use Expansa\I18n;
use Expansa\View;

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
			'title' => I18n::_t( 'Themes' ),
		]
	);

	( new Dashboard\Table( new Dashboard\Tables\Themes() ) )->print();
    ?>
</div>
