<?php

use Expansa\Facades\Hook;
use Expansa\Facades\I18n;
?>
<!DOCTYPE html>
<html lang="<?php echo I18n::locale(); ?>">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Menu</title>
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
	Hook::call('renderDashboardHeader');
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