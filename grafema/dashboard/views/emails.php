<?php

use Grafema\Hook;
use Grafema\View;

/**
 * Emails.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/emails.php
 *
 * @version 2025.1
 */
if ( ! defined( 'GRFM_PATH' ) ) {
	exit;
}

Hook::add( 'grafema_dashboard_footer', function() {
	View::print( 'views/dialogs/emails-editor' );
} );
?>
<div class="grafema-main">
	<?php ( new Dashboard\Table( new Dashboard\Tables\Emails() ) )->print(); ?>
</div>
