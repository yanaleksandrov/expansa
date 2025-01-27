<?php

use Expansa\View;
use Expansa\Hook;

/**
 * Files storage.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/media.php
 *
 * @package Expansa\Templates
 * @since   2025.1
 */
if ( ! defined( 'EX_PATH' ) ) {
	exit;
}

Hook::add( 'expansa_dashboard_footer', function() {
	echo view( 'dialogs/media-editor' );
	echo view( 'dialogs/media-uploader' );
} );
?>
<div class="expansa-main">
	<?php ( new Dashboard\Table( new Dashboard\Tables\Media() ) )->print(); ?>
    <div x-intersect="$ajax('media/get').then(({posts}) => items = posts)"></div>
</div>
