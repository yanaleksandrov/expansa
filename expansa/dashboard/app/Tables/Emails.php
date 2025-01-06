<?php
namespace Dashboard\Tables;

use Dashboard\Table\Cell;
use Dashboard\Table\Row;
use Expansa\I18n;
use Expansa\Url;

final class Emails {

	public function data(): array {
		return [
			[
				'cb'         => '<input type="checkbox" value="1">',
				'image'      => 'image',
				'title'      => 'Post title',
				'author'     => 'Yan Aleksandrov',
				'categories' => [],
				'date'       => '24 august 2024',
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
			Cell::add( 'title' )
				->title( I18n::_t( 'Name' ) )
				->flexibleWidth( '15rem' )
				->sortable()
				->view( 'title' ),
			Cell::add( 'recipients' )
				->title( I18n::_t( 'Recipients' ) )
				->flexibleWidth( '15rem' )
				->view( 'title' ),
			Cell::add( 'event' )
				->title( I18n::_t( 'Event' ) )
				->fixedWidth( '9rem' )
				->view( 'date' ),
		];
	}

	public function attributes(): array {
		return [
			'class'  => 'table',
			'x-data' => 'table',
			'x-init' => '$ajax("emails/get").then(response => items = response.items)',
		];
	}

	public function headerContent(): array {
		return [
			'title'   => I18n::_t( 'Emails' ),
			'actions' => true,
		];
	}

	public function notFoundContent(): array {
		return [
			'title'       => I18n::_t( 'No emails templates found' ),
			'description' => I18n::_t( 'Add [new email template](:emailDialog) manually', Url::site( '/dashboard/import' ) ),
			'descriptiont' => I18n::_t(
				'Add %s manually',
				sprintf( '<a href="#" @click.prevent="$dialog.open(\'tmpl-email-editor\', emailDialog)">%s</a>', I18n::_t( 'new email template' ) ),
			),
		];
	}
}
