<?php

use Grafema\Hook;
use Grafema\View;

/**
 * Files storage.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/media.php
 *
 * @package Grafema\Templates
 * @since   2025.1
 */
if ( ! defined( 'GRFM_PATH' ) ) {
	exit;
}

Hook::add( 'grafema_dashboard_footer', function() {
	View::print( 'views/dialogs/media-editor' );
	View::print( 'views/dialogs/media-uploader' );
} );
?>
<div class="grafema-main">
	<?php ( new Dashboard\Table( new Dashboard\Tables\Media() ) )->print(); ?>
    <div x-intersect="$ajax('media/get').then(({posts}) => items = posts)"></div>
</div>
