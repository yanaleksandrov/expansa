<?php

namespace Dashboard\Tables;

use Dashboard\Table\Cell;
use Dashboard\Table\Row;
use Grafema\I18n;

final class Plugins {

	public function data(): array {
		return [
			[
				'title'           => 'Classic Editor 1 and very longadable pluginsnameand hello world',
				'description'     => 'Customize WordPress with powerful, professional and intuitive fields.',
				'screenshot'      => 'https://ps.w.org/buddypress/assets/icon.svg',
				'author'          => [
					[
						'title' => 'Grafema Team',
						'href'  => 'https://core.com',
					],
				],
				'categories'      => [
					[
						'title' => 'Test category',
						'href'  => 'https://core.com',
					],
				],
				'installed'       => false,
				'active'          => false,
				'installations'   => '300k+ installations',
				'date'            => '18 September, 2024',
				'reviews'         => 23,
				'rating'          => 4,
				'grafema_version' => '2025.1',
				'version'         => '1.3.5',
			]
		];
	}

	public function rows(): array {
		return [
			Row::add()->attribute( 'class', 'table__row' )
		];
	}

	public function columns(): array {
		return [
			Cell::add( 'cb' )
				->title( '<input type="checkbox" x-bind="trigger" />' )
				->fixedWidth( '1rem' )
				->view( 'cb' ),
			Cell::add( 'image' )
				->fixedWidth( '2.5rem' )
				->view( 'image' ),
			Cell::add( 'plugin' )
				->title( I18n::_t( 'Plugin' ) )
				->flexibleWidth( '14rem' )
				->view( 'plugin' ),
			Cell::add( 'description' )
				->title( I18n::_t( 'Description' ) )
				->flexibleWidth( '14rem' )
				->view( 'raw' ),
			Cell::add( 'version' )
				->title( I18n::_t( 'Version' ) )
				->fixedWidth( '4rem' )
				->view( 'badge' ),
			Cell::add( 'active' )
				->title( I18n::_t( 'Activity' ) )
				->fixedWidth( '4rem' )
				->view( 'checkbox' ),
		];
	}

	public function attributes(): array {
		return [
			'class'  => 'table',
			'x-data' => 'table',
			'x-init' => '$ajax("extensions/get").then(response => console.log(response))',
		];
	}

	public function headerContent(): array {
		return [
			'title'   => I18n::_t( 'Plugins' ),
			'actions' => true,
			'filter'  => true,
		];
	}

	public function notFoundContent(): array {
		return [
			'icon'        => 'no-plugins',
			'title'       => I18n::_t( 'Plugins are not installed yet' ),
			'description' => I18n::_t( 'You can download them manually or install from the repository' ),
		];
	}
}
