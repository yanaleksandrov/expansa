<?php

use app\Option;
use app\User;
use Expansa\View;
use Expansa\Hook;
use Expansa\I18n;
use Expansa\Is;
use Expansa\Safe;
use Expansa\Url;

/**
 * Remove the duplicate access to the console at two addresses:
 * "dashboard" and "dashboard/index", leave only the first one.
 *
 * @since 2025.1
 */
if ( ! defined( 'EX_PATH' ) ) {
	$dashboardUrl = trim( $_SERVER['SCRIPT_URI'] ?? '' );
	if ( $dashboardUrl ) {
		header( 'Location: ' . $dashboardUrl . 'profile' );
	}
	exit;
}

$slug	   = Safe::trim( $__data['slug'] ?? '' );
$start_time = microtime( true );
// print_r(
//	Query::apply(
//		[
//			'type'		   => [ 'pages', 'media' ],
//			'status'		 => null,
//			'page'		   => 1,
//			'per_page'	   => 99,
//			'offset'		 => 2,
//			'order'		  => 'DESC',
//			'orderby'		=> 'rand',
//			's'			  => 'hello exception',
//			'sentence'	   => false,
//			'boolean_mode'   => true,
//			'slug'		   => 'hello-world',
//			'slug_strict'	=> true,
//			'title'		  => 'Title 6',
//			'status'		 => 'draft',
//			'nicename'	   => 'alexandrov',
//			'author__in'	 => [ 2, 3, 4, 5 ],
//			'author__not_in' => [ 4 ],
//			'post__in'	   => [ 2, 3, 4, 5 ],
//			'post__not_in'   => [ 4 ],
//			'parent__in'	 => [ 2, 3, 4, 5 ],
//			'parent__not_in' => [ 4 ],
//			'discussion'	 => 'closed',
//			'comments'	   => [
//				'value'   => 8,
//				'compare' => '<',
//			],
//			'views'		  => [
//				'value'   => 0,
//				'compare' => '>=',
//			],
//			'dates'		  => [
//				'relation' => 'AND',
//				[
//					'human_date_time' => '-23 days',
//					'compare'		 => '<=',
//				],
//				[
//					'column'  => 'modified',
//					'compare' => 'BETWEEN',
//					'year'	=> [ 2015, 2016 ],
//				],
//			],
//			'fields'   => [
//				'relation' => 'AND',
//				[
//					'key'	 => 'price',
//					'value'   => '32434',
//					'compare' => '<=',
//					'type'	=> 'NUMERIC',
//				],
//				[
//					'key'	 => 'price',
//					'value'   => '30006',
//					'compare' => '>=',
//				],
//			],
//		]
//	)
// );
// echo 'Time:  ' . number_format( ( microtime( true ) - $start_time ), 4 ) . " Seconds\n";
// exit;
?>
<!DOCTYPE html>
<html lang="<?php echo I18n::locale(); ?>">
<head>
	<meta charset="<?php Option::attr( 'charset', 'UTF-8' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Menu</title>
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
	?>
</head>
<body x-data="expansa" @keydown.window.prevent.ctrl.s="$notification.add(notifications.ctrlS)">
	<?php if ( Is::installed() && User::logged() ) { ?>
		<div class="expansa" :class="showMenu && 'active'">
			<div class="expansa-bar">
				<div class="expansa-bar-menu" :class="showMenu && 'active'" @click="showMenu = !showMenu">
					<i class="ph ph-list"></i>
				</div>
				<?php echo view( 'views/menu-bar' ); ?>

				<details class="expansa-search" x-data="search" x-bind="wrapper">
					<summary class="expansa-search-btn" x-bind="button">
						<i class="ph ph-magnifying-glass"></i> <?php t_attr( 'Search...' ); ?> <code>Ctrl+K</code>
					</summary>
					<div class="expansa-search-box">
						<div class="field field--lg field--outline">
							<label class="field-item">
								<input class="expansa-search-input" type="search" name="search" placeholder="<?php t_attr( 'Search...' ); ?>" x-bind="input" @input.debounce.250ms="$ajax('search').then(() => links = [{url: '', text: 'Страницы'}, {url: '/dashboard/themes', text: 'Привет'}, {url: '/dashboard/plugins', text: 'Привет'}])">
							</label>
						</div>
						<template x-if="links.length">
							<ul class="expansa-search-results">
								<template x-for="(link, i) in links" :key="i">
									<li class="expansa-search-item" :class="link.url && {'active': i === currentIdx}">
										<template x-if="link.url">
											<a class="expansa-search-link" :href="link.url">
												<span class="expansa-search-text" x-html="link.text"></span>
												<span class="t-muted"><?php t( 'Jump to' ); ?></span>
											</a>
										</template>
										<template x-if="!link.url">
											<span class="expansa-search-header" x-html="link.text"></span>
										</template>
									</li>
								</template>
							</ul>
						</template>
						<template x-if="!links.length">
							<div class="expansa-search-results">
								<?php
								echo view(
									'global/state',
									[
										'icon'        => 'ufo',
										'title'       => t( 'Nothing found' ),
										'description' => t( 'Try to write something, there will be search results here' ),
									]
								);
								?>
							</div>
						</template>
						<div class="expansa-search-help">
							<div class="df aic g-1"><i class="ph ph-arrow-up"></i><i class="ph ph-arrow-down"></i> <?php t( 'Move' ); ?></div>
							<div class="df aic g-1"><i>Esc</i> <?php t( 'Close' ); ?></div>
							<div class="df aic g-1"><i class="ph ph-arrow-elbow-down-left"></i> <?php t( 'Select' ); ?></div>
						</div>
					</div>
				</details>

				<?php echo view( 'views/global/user-account' ); ?>
			</div>
			<!-- interface panel start -->
			<div class="expansa-panel">
				<a href="<?php echo Url::site(); ?>" target="_blank">
					<img src="<?php echo Url::site( '/dashboard/assets/images/logo.svg' ); ?>" width="34" height="34" alt="Expansa Logo">
				</a>
				<?php echo view( 'views/menu-panel' ); ?>
			</div>
			<!-- interface sidebar start -->
			<?php
			echo view( 'views/menu' );

			echo view( 'views/' . $slug );
			?>
			<!-- interface board start -->
			<div class="expansa-board">
				<a href="#" class="dif g-1 aic t-dark" title="Get Support"><i class="ph ph-headset fs-12"></i> support</a>
				<a href="#" class="dif g-1 aic t-dark" title="Expansa CMS version"><i class="ph ph-git-branch fs-12"></i> 2025.1</a>
			</div>
		</div>
		<?php
	} else {
		?>
		<div class="df aic jcc p-6">
			<?php echo view( 'views/' . $slug ); ?>
		</div>
		<?php
	}
	?>

	<!-- dialog windows start -->
	<div class="dialog" :class="$store.dialog?.class" id="expansa-dialog">
		<div class="dialog-wrapper" @click.outside="$dialog.close()">
			<div class="dialog-header">
				<template x-if="$store.dialog?.title">
					<h6 class="dialog-title" x-text="$store.dialog.title"></h6>
				</template>
				<button class="dialog-close" type="button" @click="$dialog.close()"></button>
			</div>
			<div class="dialog-content" data-content></div>
		</div>
	</div>

	<!-- notifications start -->
	<template x-if="$store.notifications.length">
		<div class="notifications">
			<template x-for="(notification, i) in $store.notifications">
				<div class="notifications-item" :class="notification.class" :style="`--notice-scale: ${1 - ($store.notifications.length - i - 1) * 0.005}`">
					<div class="notifications-wrapper">
						<template x-if="notification.type">
							<div class="notifications-icon">
								<i class="ph ph-bell-ringing t-gray" x-show="notification.type === 'info'"></i>
								<i class="ph ph-siren t-red" x-show="notification.type === 'error'"></i>
								<i class="ph ph-check t-green" x-show="notification.type === 'success'"></i>
								<i class="ph ph-shield-warning t-orange" x-show="notification.type === 'warning'"></i>
							</div>
						</template>
						<div class="notifications-text" x-text="notification.message"></div>
						<div class="notifications-close" :style="notification.duration && `--notice-animation: ${notification.animation}`" @click="$notification.close(notification.id)"></div>
					</div>
				</div>
			</template>
		</div>
	</template>
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