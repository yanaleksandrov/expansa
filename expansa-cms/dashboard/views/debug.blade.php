<?php
use Expansa\I18n;
?>
<!DOCTYPE html>
<html lang="<?php echo I18n::locale(); ?>">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?php echo t( 'Expansa Debug' ); ?></title>
	<link rel="apple-touch-icon" sizes="180x180" href="/dashboard/assets/images/favicons/apple-touch-icon.png">
	<link rel="icon" type="image/png" sizes="32x32" href="/dashboard/assets/images/favicons/favicon-32x32.png">
	<link rel="icon" type="image/png" sizes="16x16" href="/dashboard/assets/images/favicons/favicon-16x16.png">
	<link rel="manifest" href="/dashboard/assets/images/favicons/site.webmanifest">
	<link rel="mask-icon" href="/dashboard/assets/images/favicons/safari-pinned-tab.svg" color="#5bbad5">

	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet">
	<link rel="stylesheet" id="errors-css" href="/dashboard/assets/css/errors.css">
	<link rel="stylesheet" id="prism-css" href="/dashboard/assets/css/prism.css">
</head>
<body class="errors">
	<header class="errors-header">
		<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 256 256">
			<path fill="currentColor" d="M128 24a104 104 0 1 0 104 104A104 104 0 0 0 128 24Zm0 192a88 88 0 1 1 88-88 88 88 0 0 1-88 88Zm-8-80V80a8 8 0 0 1 16 0v56a8 8 0 0 1-16 0Zm20 36a12 12 0 1 1-12-12 12 12 0 0 1 12 12Z"/>
		</svg>
		<h3 class="errors-title">{{ $title }}: {{ $context }}</h3>
	</header>
	<div class="errors-content">
		<p>{{ $description }}</p>
	</div>
	<div class="errors-wrapper">
	    <?php if ( $details ) : ?>
			<div class="errors-description">
				<p><strong><?php echo t( 'Arguments:' ); ?></strong></p>
				<dl>
	                <?php foreach ( $details as $detail ) : ?>
						<dt><code>#<?php echo $detail->key; ?> <?php echo t( 'required type:' ); ?> <em>(<?php echo $detail->type; ?>)</em></code></dt>
						<dd><p><?php echo t( 'incoming value:' ); ?>: <?php print_r( $detail->value ); ?></p></dd>
	                <?php endforeach; ?>
				</dl>
			</div>
	    <?php endif; ?>
		<div class="errors-data">
			<ul class="errors-navigation">
	            <?php foreach ( $traces as $trace ) : ?>
					<li class="errors-navigation-item"><code><strong><?php echo $trace->line; ?>:</strong></code> <?php echo $trace->file; ?></li>
	            <?php endforeach; ?>
			</ul>
			<pre class="errors-source" x-highlight.php>
				<code class="language-php">{{ $code }}</code>
			</pre>
		</div>
	</div>

	<script id="prism-js" src="/dashboard/assets/js/prism.min.js"></script>
	<script id="expansa-js" src="/dashboard/assets/js/expansa.min.js"></script>
	<script id="alpine-js" src="/dashboard/assets/js/alpine.min.js"></script>
</body>
</html>