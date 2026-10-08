<?php
/**
 * Page of an uncaught error, rendered by Expansa\Debug\Manager::render().
 * Plain PHP and English only: the error may come from translations, hooks or the facades.
 *
 * @var string $title
 * @var string $message
 * @var string $id
 * @var string $file
 * @var int    $line
 * @var array  $trace
 * @var array  $arguments
 * @var array  $previous
 * @var array  $request
 */
$h = fn ( mixed $value ): string => htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' );

$place = function ( string $file, int $line ) use ( $h ): string {
	$path = $h( dirname( $file ) . DIRECTORY_SEPARATOR ) . '<u>' . $h( basename( $file ) ) . '</u>';

	return 'on line <em>' . $line . '</em> in ' . $path;
};

$frame = function ( array $frame, bool $open ) use ( $h ): string {
	$call = $frame['call'] ? ' <code>' . $h( $frame['call'] ) . '</code>' : '';
	$code = $frame['code'] === '' ? '' : '<pre class="errors-source" data-start="' . $frame['start'] . '" data-line="' . $frame['line'] . '" u-highlight.php><code class="language-php">' . $h( $frame['code'] ) . '</code></pre>';

	return '<details class="errors-frame"' . ( $open ? ' open' : '' ) . '><summary><code><strong>' . $frame['line'] . ':</strong></code> ' . $h( $frame['file'] ) . $call . '</summary>' . $code . '</details>';
};

$group = fn ( array $frames ): string => '<details class="errors-group"><summary>' . count( $frames ) . ' core and vendor frames</summary>' . implode( '', array_map( fn ( array $item ) => $frame( $item, false ), $frames ) ) . '</details>';
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?php echo $h( $file ? 'Expansa Debug' : $title ); ?></title>
	<link rel="apple-touch-icon" sizes="180x180" href="/dashboard/assets/favicon/apple-touch-icon.png">
	<link rel="icon" type="image/png" sizes="32x32" href="/dashboard/assets/favicon/favicon-32x32.png">
	<link rel="icon" type="image/png" sizes="16x16" href="/dashboard/assets/favicon/favicon-16x16.png">
	<link rel="manifest" href="/dashboard/assets/favicon/site.webmanifest">
	<link rel="mask-icon" href="/dashboard/assets/favicon/safari-pinned-tab.svg" color="#5bbad5">

	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet">
	<link rel="stylesheet" id="errors-css" href="/dashboard/assets/css/errors.css">
	<?php if ( $file ) : ?>
		<link rel="stylesheet" id="prism-css" href="/dashboard/assets/css/prism.css">
	<?php endif; ?>
</head>
<body class="errors">
	<header class="errors-header">
		<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 256 256">
			<path fill="currentColor" d="M128 24a104 104 0 1 0 104 104A104 104 0 0 0 128 24Zm0 192a88 88 0 1 1 88-88 88 88 0 0 1-88 88Zm-8-80V80a8 8 0 0 1 16 0v56a8 8 0 0 1-16 0Zm20 36a12 12 0 1 1-12-12 12 12 0 0 1 12 12Z"/>
		</svg>
		<h3 class="errors-title"><?php echo $h( "$title: $message" ); ?></h3>
	</header>
	<div class="errors-content">
		<?php if ( $file ) : ?>
			<p><?php echo ucfirst( $place( $file, $line ) ); ?></p>
			<?php foreach ( $previous as $cause ) : ?>
				<p>Caused by <?php echo $h( "{$cause['title']}: {$cause['message']}" ); ?> <?php echo $place( $cause['file'], $cause['line'] ); ?></p>
			<?php endforeach; ?>
		<?php endif; ?>
		<?php if ( $id ) : ?>
			<p class="errors-id">Error ID: <code><?php echo $h( $id ); ?></code></p>
		<?php endif; ?>
	</div>
	<?php if ( $file ) : ?>
		<div class="errors-wrapper">
			<?php if ( $arguments ) : ?>
				<div class="errors-description">
					<p><strong>Arguments:</strong></p>
					<dl>
						<?php foreach ( $arguments as $argument ) : ?>
							<dt><code>#<?php echo $argument['position']; ?> <em>(<?php echo $h( $argument['type'] ); ?>)</em></code></dt>
							<dd><p><?php echo $h( $argument['value'] ); ?></p></dd>
						<?php endforeach; ?>
					</dl>
				</div>
			<?php endif; ?>
			<div class="errors-frames">
				<?php
				$collapsed = [];
				foreach ( $trace as $i => $current ) {
					if ( $current['collapsed'] ) {
						$collapsed[] = $current;
						continue;
					}

					if ( $collapsed ) {
						echo $group( $collapsed );
						$collapsed = [];
					}

					echo $frame( $current, $i === 0 );
				}

				if ( $collapsed ) {
					echo $group( $collapsed );
				}
				?>
			</div>
			<?php if ( $request ) : ?>
				<div class="errors-description errors-request">
					<p><strong>Request:</strong></p>
					<dl>
						<?php foreach ( $request as $name => $value ) : ?>
							<dt><code><?php echo $h( $name ); ?></code></dt>
							<dd><p><?php echo $h( $value ); ?></p></dd>
						<?php endforeach; ?>
					</dl>
				</div>
			<?php endif; ?>
		</div>

		<script id="prism-js" src="/dashboard/assets/js/prism.min.js"></script>
		<script id="youla-js" src="/dashboard/assets/js/youla.js"></script>
		<script id="youla-expansa-js" src="/dashboard/assets/js/youla-expansa.js"></script>
	<?php endif; ?>
</body>
</html>
