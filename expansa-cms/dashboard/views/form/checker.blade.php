<?php

use Expansa\Facades\Safe;

/**
 * Check system.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/fields/checker.php
 *
 * @package Expansa\Templates
 */
if ( ! defined( 'EX_PATH' ) ) {
	exit;
}

$class  = Safe::class($__data['class'] ?? '');
$checks = [
	'pdo'        => t('PDO PHP Extension'),
	'curl'       => t('cURL PHP Extension'),
	'mbstring'   => t('Mbstring PHP Extension'),
	'gd'         => t('GD PHP Extension'),
	'memory'     => t('128MB or more allocated memory'),
	'php'        => t('PHP version %s or higher', EX_REQUIRED_PHP_VERSION),
	'connection' => t('Testing the database connection'),
	'mysql'      => t('MySQL version %s or higher', EX_REQUIRED_MYSQL_VERSION),
];
?>
<div class="<?php echo $class; ?>">
	<ul class="dg g-1">
		@foreach ($checks as $icon => $title)
			<li class="df aic">
				<span class="badge badge--xl badge--round badge--icon" :class="approved.{{ $icon }} === undefined ? 'badge--load' : (approved.{{ $icon }} ? 't-green' : 't-red')">
					<i class="ph" :class="approved.{{ $icon }} ? 'ph-check' : 'ph-x'"></i>
				</span>
				<span class="ml-4">{{ $title }}</span>
			</li>
		@endforeach
	</ul>
</div>
