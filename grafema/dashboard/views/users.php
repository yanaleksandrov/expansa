<?php
/**
 * Users list.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/users.php
 *
 * @package Grafema\Templates
 * @since   2025.1
 */
if ( ! defined( 'GRFM_PATH' ) ) {
	exit;
}
?>
<div class="grafema-main">
	<?php ( new Dashboard\Table( new Dashboard\Tables\Users() ) )->print(); ?>
</div>
