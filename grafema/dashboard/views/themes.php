<?php

use Grafema\I18n;
use Grafema\View;

/**
 * Themes list.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/themes.php
 *
 * @package Grafema\Templates
 * @since   2025.1
 */
if ( ! defined( 'GRFM_PATH' ) ) {
	exit;
}
?>
<div class="grafema-main">
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
