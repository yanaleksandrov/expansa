<?php
/**
 * Search in dashboard.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/parts/search.blade.php
 *
 * @package Expansa\Templates
 */
?>
<details class="expansa-search" x-data="search" x-bind="wrapper">
	<summary class="expansa-search-btn" x-bind="button">
		<i class="ph ph-magnifying-glass"></i> {{ t('Search...') }} <code>Ctrl+K</code>
	</summary>
	<div class="expansa-search-box">
		<div class="field field--lg field--outline">
			<label class="field-item">
				<input class="expansa-search-input" type="search" name="search" placeholder="{{ t('Search...') }}" x-bind="input" @input.debounce.250ms="$ajax('search').then(() => links = [{url: '', text: 'Страницы'}, {url: '/dashboard/themes', text: 'Привет'}, {url: '/dashboard/plugins', text: 'Привет'}])">
			</label>
		</div>
		<template x-if="links.length">
			<ul class="expansa-search-results">
				<template x-for="(link, i) in links" :key="i">
					<li class="expansa-search-item" :class="link.url && {'active': i === currentIdx}">
						<template x-if="link.url">
							<a class="expansa-search-link" :href="link.url">
								<span class="expansa-search-text" x-html="link.text"></span>
								<span class="t-muted">{{ t( 'Jump to' ) }}</span>
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
			<div class="df aic g-1"><i class="ph ph-arrow-up"></i><i class="ph ph-arrow-down"></i> {{ t( 'Move' ) }}</div>
			<div class="df aic g-1"><i>Esc</i> {{ t( 'Close' ) }}</div>
			<div class="df aic g-1"><i class="ph ph-arrow-elbow-down-left"></i> {{ t( 'Select' ) }}</div>
		</div>
	</div>
</details>
