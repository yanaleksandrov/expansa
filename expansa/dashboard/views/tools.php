<?php
/**
 * Expansa dashboard tools.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/tools.php
 *
 * @package Expansa\Templates
 * @since   2025.1
 */
if ( ! defined( 'EX_PATH' ) ) {
	exit;
}
?>
<div class="expansa-main p-7 bg-gray-lt">
	<?php Dashboard\Form::print( EX_DASHBOARD . 'forms/tools-list.php' ); ?>
</div>
