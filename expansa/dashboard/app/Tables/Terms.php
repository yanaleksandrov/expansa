<?php
namespace Dashboard\Tables;

use Dashboard\Table\Cell;
use Dashboard\Table\Row;
use Expansa\I18n;

final class Terms {

	public function data(): array {
//		Hook::add( 'expansa_dashboard_data', function( $data ) {
//			$data['items'] = [
//				[
//					'image' => 'https://dev.codyshop.ru/wp-content/themes/rgbcode/screenshot.png',
//					'title' => 'Hello World',
//					'slug'  => 'hello-world',
//					'count' => 23,
//				],
//				[
//					'image' => 'https://dev.codyshop.ru/wp-content/themes/rgbcode/screenshot.png',
//					'title' => 'Uncategorized',
//					'slug'  => 'uncategorized',
//					'count' => 5,
//				],
//			];
//			return $data;
//		} );

		return [ 435 ];
	}

	public function dataBefore(): string {
		return '';
	}

	public function dataAfter(): string {
		ob_start();
		?>
		<p><?php I18n::t( 'Deleting a category does not delete the posts in that category. Instead, posts that were only assigned to the deleted category are set to the default category Uncategorized. The default category cannot be deleted.' ); ?></p>
		<p><?php I18n::t( 'Image' ); ?></p>
		<?php
		return ob_get_clean();
	}

	public function rows(): array {
		return [
			Row::add()->attribute( 'class', 'table__row' ),
		];
	}

	public function columns(): array {
		return [
			Cell::add( 'cb' )->title( '<input type="checkbox" x-bind="trigger" />' )->fixedWidth( '1rem' )->view( 'cb' ),
			Cell::add( 'image' )->title( I18n::_t( 'Image' ) )->fixedWidth( '2.5rem' )->view( 'image' ),
			Cell::add( 'title' )->title( I18n::_t( 'Title' ) )->view( 'title' ),
			Cell::add( 'slug' )->title( I18n::_t( 'Slug' ) )->view( 'raw' ),
			Cell::add( 'count' )->title( I18n::_t( 'Count' ) )->fixedWidth( '2rem' )->view( 'raw' ),
		];
	}

	public function attributes(): array {
		return [
			'class'  => 'table',
			'x-data' => 'table',
		];
	}

	public function notFoundContent(): array {
		return [
			'title'       => I18n::_t( 'Terms not found' ),
			'description' => I18n::_t( 'You don\'t have any themes installed yet, <a @click="$dialog.open(\'tmpl-post-editor\')">download them</a>' ),
		];
	}

	public function headerContent(): array {
		return [
			'title'   => I18n::_t( 'Terms' ),
			'actions' => true,
			'filter'  => true,
		];
	}

	public function notFoundAfter(): string {
		return '';
	}

	public function notFoundBefore(): string {
		return '';
	}
}
