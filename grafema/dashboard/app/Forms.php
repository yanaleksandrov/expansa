<?php
namespace Dashboard;

/**
 * Forms builder.
 *
 * @package Grafema
 */
class Forms {

	public function __construct(
		public array $items = [
			'text'            => Forms\Fields\Input::class,
			'color'           => Forms\Fields\Input::class,
			'date'            => Forms\Fields\Input::class,
			'datetime-local'  => Forms\Fields\Input::class,
			'email'           => Forms\Fields\Input::class,
			'month'           => Forms\Fields\Input::class,
			'range'           => Forms\Fields\Input::class,
			'search'          => Forms\Fields\Input::class,
			'tel'             => Forms\Fields\Input::class,
			'time'            => Forms\Fields\Input::class,
			'url'             => Forms\Fields\Input::class,
			'week'            => Forms\Fields\Input::class,

			'builder'         => Forms\Fields\Input::class,
			'checkbox'        => Forms\Fields\Input::class,
			'custom'          => Forms\Fields\Input::class,
			'details'         => Forms\Fields\Input::class,
			'divider'         => Forms\Fields\Input::class,
			'file'            => Forms\Fields\Input::class,
			'header'          => Forms\Fields\Input::class,
			'hidden'          => Forms\Fields\Input::class,
			'image'           => Forms\Fields\Input::class,
			'input'           => Forms\Fields\Input::class,
			'layout-group'    => Forms\Fields\Input::class,
			'layout-step'     => Forms\Fields\Input::class,
			'layout-tab'      => Forms\Fields\Input::class,
			'layout-tab-menu' => Forms\Fields\Input::class,
			'media'           => Forms\Fields\Input::class,
			'number'          => Forms\Fields\Input::class,
			'password'        => Forms\Fields\Input::class,
			'progress'        => Forms\Fields\Input::class,
			'radio'           => Forms\Fields\Input::class,
			'select'          => Forms\Fields\Input::class,
			'submit'          => Forms\Fields\Input::class,
			'textarea'        => Forms\Fields\Input::class,
			'uploader'        => Forms\Fields\Input::class,

			'editor'          => Forms\Fields\Input::class,
			'gallery'         => Forms\Fields\Input::class,
			'repeater'        => Forms\Fields\Input::class,
			'message'         => Forms\Fields\Input::class,
		]
	) {}

	public static function configure( array $items ) {
		self::$items = array_merge( self::$items, $items );
	}
}
