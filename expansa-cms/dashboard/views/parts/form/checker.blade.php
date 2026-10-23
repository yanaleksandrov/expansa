<?php

use Expansa\Builders\Form\Data;

/**
 * Check system.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/fields/checker.php
 *
 * @var Data $field
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;

$requirements = [
	'pdo'        => t('PDO PHP Extension'),
	'curl'       => t('cURL PHP Extension'),
	'mbstring'   => t('Mbstring PHP Extension'),
	'dom'        => t('DOM PHP Extension'),
	'gd'         => t('GD PHP Extension'),
	'memory'     => t('At least 128MB of memory'),
	'php'        => t('PHP version %s or higher', EX_REQUIRED_PHP_VERSION),
	'connection' => t('Testing the database connection'),
	'mysql'      => t('MySQL version %s or higher', EX_REQUIRED_MYSQL_VERSION),
];
?>
<div class="{{ $field->class ?: 'field' }}">
	<ul class="dg g-1">
		@foreach ($requirements as $requirement => $title)
			<li class="df aic">
				<span class="badge badge--xl badge--round badge--icon" :class="compat.{{ $requirement }} === undefined ? 'is-loading' : (compat.{{ $requirement }} ? 't-green' : 't-red')">
					<i class="ph" :class="compat.{{ $requirement }} ? 'ph-check' : 'ph-x'"></i>
				</span>
				<span class="ml-4">{{ $title }}</span>
			</li>
		@endforeach
	</ul>
</div>
