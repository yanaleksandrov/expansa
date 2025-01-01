<?php

use Expansa\Hook;
use Expansa\View;

/**
 * Pages list.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/pages.php
 *
 * @package Expansa\Templates
 * @since   2025.1
 */
if ( ! defined( 'EX_PATH' ) ) {
	exit;
}

Hook::add( 'expansa_dashboard_footer', function() {
	View::print( 'views/dialogs/posts-editor' );
} );
?>
<div class="expansa-main">
	<?php ( new Dashboard\Table( new Dashboard\Tables\Pages() ) )->print(); ?>
</div>
