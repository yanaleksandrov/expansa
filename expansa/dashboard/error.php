<?php
$phpVersion = strval(phpversion());
$dbVersion  = Expansa\Db::version();

$phpVersionIsCompatible = version_compare(EX_REQUIRED_PHP_VERSION, $phpVersion, '<=');
$dbVersionIsCompatible  = version_compare(EX_REQUIRED_MYSQL_VERSION, $dbVersion, '<=');

if ($phpVersionIsCompatible && $dbVersionIsCompatible) {
    return;
}

$serverProtocol = $_SERVER['SERVER_PROTOCOL'] ?? '';
if (! in_array($serverProtocol, ['HTTP/1.1', 'HTTP/2', 'HTTP/2.0', 'HTTP/3'])) {
    $serverProtocol = 'HTTP/1.0';
}

header(sprintf('%s 500 Internal Server Error', $serverProtocol), true, 500);
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Expansa Requirements</title>
	<link rel="apple-touch-icon" sizes="180x180" href="/dashboard/assets/images/favicons/apple-touch-icon.png">
	<link rel="icon" type="image/png" sizes="32x32" href="/dashboard/assets/images/favicons/favicon-32x32.png">
	<link rel="icon" type="image/png" sizes="16x16" href="/dashboard/assets/images/favicons/favicon-16x16.png">
	<link rel="manifest" href="/dashboard/assets/images/favicons/site.webmanifest">
	<link rel="mask-icon" href="/dashboard/assets/images/favicons/safari-pinned-tab.svg" color="#5bbad5">

	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet">
	<link rel="stylesheet" id="errors-css" href="/dashboard/assets/css/errors.css">
</head>
<body class="errors">
	<?php if (!$phpVersionIsCompatible) : ?>
		<div class="errors-header">
			<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 256 256">
				<path fill="currentColor" d="M128 24a104 104 0 1 0 104 104A104 104 0 0 0 128 24Zm0 192a88 88 0 1 1 88-88 88 88 0 0 1-88 88Zm-8-80V80a8 8 0 0 1 16 0v56a8 8 0 0 1-16 0Zm20 36a12 12 0 1 1-12-12 12 12 0 0 1 12 12Z"/>
			</svg>
			<div class="errors-title">
	            <?php
	            echo t(
	                'Your server is running PHP version ":phpVersion" but Expansa :expansaVersion requires at least :phpRequiredVersion.',
                    $phpVersion,
                    EX_VERSION,
                    EX_REQUIRED_PHP_VERSION
	            );
	            ?>
			</div>
		</div>
	<?php endif; ?>
	<?php if (!$dbVersionIsCompatible) : ?>
		<div class="errors-header">
			<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 256 256">
				<path fill="currentColor" d="M128 24a104 104 0 1 0 104 104A104 104 0 0 0 128 24Zm0 192a88 88 0 1 1 88-88 88 88 0 0 1-88 88Zm-8-80V80a8 8 0 0 1 16 0v56a8 8 0 0 1-16 0Zm20 36a12 12 0 1 1-12-12 12 12 0 0 1 12 12Z"/>
			</svg>
			<div class="errors-title">
				<?php
				echo t(
	                'Your server is running PHP version ":dbVersion" but Expansa :expansaVersion requires at least :dbRequiredVersion.',
	                $dbVersion,
	                EX_VERSION,
	                EX_REQUIRED_MYSQL_VERSION
	            );
				?>
			</div>
		</div>
	<?php endif; ?>
</body>
</html>
<?php
exit;