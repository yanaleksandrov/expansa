<?php

use Expansa\View;
use Expansa\Hook;

/**
 * Emails.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/emails.php
 *
 * @version 2025.1
 */
if ( ! defined( 'EX_PATH' ) ) {
	exit;
}

Hook::add( 'expansa_dashboard_footer', function() {
	echo view( 'dialogs/emails-editor' );
} );
?>
<div class="expansa-main">
	<?php ( new Dashboard\Table( new Dashboard\Tables\Emails() ) )->print(); ?>
</div>
