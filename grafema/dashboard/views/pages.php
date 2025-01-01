<?php

use Grafema\Hook;
use Grafema\View;

/**
 * Pages list.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/pages.php
 *
 * @package Grafema\Templates
 * @since   2025.1
 */
if ( ! defined( 'GRFM_PATH' ) ) {
	exit;
}

Hook::add( 'grafema_dashboard_footer', function() {
	View::print( 'views/dialogs/posts-editor' );
} );
?>
<div class="grafema-main">
	<?php ( new Dashboard\Table( new Dashboard\Tables\Pages() ) )->print(); ?>
</div>
