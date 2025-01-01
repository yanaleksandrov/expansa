<?php
use Grafema\I18n;

/**
 * Boilerplate plugin.
 *
 * @since 2025.1
 */
return new class extends Grafema\Plugin {

	public function __construct() {
		$this
			->setVersion( '2024.9' )
			->setAuthor( 'Grafema Team' )
			->setName( 'Grafema Plugin Boilerplate' )
			->setDescription( I18n::_t( 'Plugin Boilerplate Description' ) );
	}

	public static function launch()
	{
		// TODO: Implement launch() method.
	}

	public static function activate()
	{
		// do something when plugin is activated
	}

	public static function deactivate()
	{
		// do something when plugin is deactivated
	}

	public static function install()
	{
		// do something when plugin is installed
	}

	public static function uninstall()
	{
		// do something when plugin is uninstalled
	}
};
