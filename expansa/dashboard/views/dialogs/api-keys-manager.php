<?php
use Expansa\View;

/**
 * Template for API keys manager.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/dialogs/api-keys-manager.php
 *
 * @package Expansa\Templates
 * @since   2025.1
 */
if ( ! defined( 'EX_PATH' ) ) {
	exit;
}
?>

<!-- email editor template start -->
<template id="tmpl-api-keys-manager">
	<?php Dashboard\Form::print( EX_DASHBOARD . 'forms/api-keys-manager.php' ); ?>
</template>
