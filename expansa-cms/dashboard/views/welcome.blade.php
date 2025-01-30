<?php
use app\Option;
use Expansa\Hook;
use Expansa\I18n;
?>
<!DOCTYPE html>
<html lang="<?php echo I18n::locale(); ?>">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Menu</title>
	<link rel="apple-touch-icon" sizes="180x180" href="{{ url('/dashboard/assets/images/favicons/apple-touch-icon.png') }}">
	<link rel="icon" type="image/png" sizes="32x32" href="{{ url('/dashboard/assets/images/favicons/favicon-32x32.png') }}">
	<link rel="icon" type="image/png" sizes="16x16" href="{{ url('/dashboard/assets/images/favicons/favicon-16x16.png') }}">
	<link rel="manifest" href="{{ url('/dashboard/assets/images/favicons/site.webmanifest') }}">
	<link rel="mask-icon" href="{{ url('/dashboard/assets/images/favicons/safari-pinned-tab.svg') }}" color="#5bbad5">

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
	?>
</head>
<body class="df jcc p-6">
    <?php
	echo view($slug);

	/**
	 * Prints scripts or data before the closing body tag on the dashboard.
	 *
	 * @since 2025.1
	 */
	Hook::call( 'renderDashboardFooter' );
	?>
</body>
</html>