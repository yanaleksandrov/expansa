<?php
/**
 * Document of the pages without the dashboard: sign-in, sign-up, reset password, the installer.
 *
 * @var string           $page  Template of the page, e.g. `screens/sign-in`; it gets the same data.
 * @var string           $title Document title.
 * @var App\Support\Site $site  Settings of the site: name, language, charset, addresses, version; shared with every view.
 */

use Expansa\Facades\Hook;
?>
<!DOCTYPE html>
<html lang="{{ $site->locale }}">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>{{ $title ?? t('Dashboard') }}</title>
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
	 * @since 2027.1
	 */
	Hook::run('renderDashboardHeader');
	?>
</head>
<body class="df jcc p-6">
    <?php
	echo view($page, $__data);

	/**
	 * Prints scripts or data before the closing body tag on the dashboard.
	 *
	 * @since 2027.1
	 */
	Hook::run( 'renderDashboardFooter' );
	?>
</body>
</html>