<?php
use Expansa\Hook;
use Expansa\I18n;
use Expansa\Url;

/*
 * Expansa install wizard.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/install.php
 *
 * @package Expansa\Templates
 * @since   2025.1
 */
if ( ! defined( 'EX_PATH' ) ) {
	exit;
}
?>
<!DOCTYPE html>
<html lang="<?php echo I18n::locale(); ?>">
	<head>
		<meta charset="UTF-8">
		<meta name="theme-color" content="#ffffff">
		<meta name="msapplication-TileColor" content="#da532c">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<title><?php I18n::t( 'Install Expansa' ); ?></title>
		<link rel="apple-touch-icon" sizes="180x180" href="/dashboard/assets/images/favicons/apple-touch-icon.png">
		<link rel="icon" type="image/png" sizes="32x32" href="/dashboard/assets/images/favicons/favicon-32x32.png">
		<link rel="icon" type="image/png" sizes="16x16" href="/dashboard/assets/images/favicons/favicon-16x16.png">
		<link rel="manifest" href="/dashboard/assets/images/favicons/site.webmanifest">
		<link rel="mask-icon" href="/dashboard/assets/images/favicons/safari-pinned-tab.svg" color="#5bbad5">

		<link rel="preconnect" href="https://fonts.googleapis.com">
		<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
		<link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet">
		<style>
            :root {
                --expansa-font-text: "Inter", sustem-ui, sans-serif !important;
            }
		</style>
		<?php
		/**
		 * Prints scripts or data before the closing body tag on the dashboard.
		 *
		 * @since 2025.1
		 */
		Hook::call( 'renderDashboardHeader' );

		ini_set('display_errors', '1');
		ini_set('display_startup_errors', '1');
		error_reporting(E_ALL);
		?>
	</head>
	<body class="df jcc p-6" x-data="expansa">
        <div class="mw-400">
            <div class="df jcc">
                <img src="<?php echo Url::site( '/dashboard/assets/images/logo-decorate.svg' ); ?>" width="200" height="117" alt="Expansa CMS">
            </div>
            <?php Dashboard\Form::print( EX_PATH . 'dashboard/forms/system-install.php' ); ?>
        </div>
		<?php
		/**
		 * Prints scripts or data before the closing body tag on the dashboard.
		 *
		 * @since 2025.1
		 */
		Hook::call( 'renderDashboardFooter' );
		?>
	</body>
</html>
