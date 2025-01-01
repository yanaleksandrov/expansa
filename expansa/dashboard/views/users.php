<?php
/**
 * Users list.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/users.php
 *
 * @package Expansa\Templates
 * @since   2025.1
 */
if ( ! defined( 'EX_PATH' ) ) {
	exit;
}
?>
<div class="expansa-main">
	<?php ( new Dashboard\Table( new Dashboard\Tables\Users() ) )->print(); ?>
</div>
