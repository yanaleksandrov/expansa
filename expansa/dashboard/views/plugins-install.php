<?php

use Expansa\I18n;
use Expansa\View;

/**
 * Addons list for install.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/plugins-install.php
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
			'title'  => I18n::_t( 'Add Plugins' ),
			'search' => true,
		]
	);

	( new Dashboard\Table( new Dashboard\Tables\PluginsInstall() ) )->print();
	?>
</div>
