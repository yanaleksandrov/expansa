<?php
use Grafema\Db;
use Grafema\I18n;
use Grafema\Is;
use Grafema\Hook;
use Grafema\Debug;

/**
 * QueryMonitor plugin.
 *
 * @since 2025.1
 */
return new class extends Grafema\Plugin {

	public function __construct() {
		$this
			->setVersion( '2024.9' )
			->setName( 'Smarto' )
			->setAuthor( 'Grafema Team' )
			->setDescription( I18n::_t( 'The developer tools panel for Grafema' ) );
	}

	/**
	 * This is sample function.
	 *
	 * This is big description of current function.
	 * End second...
	 *
	 * @param mixed  $str     Some parameter description
	 * @param bool   $ret     Some return parameter. Default true.
	 * @param string $content Description. Default null. If edit,
	 *                        and other content.
	 *                        Start new paragraph!
	 * @param bool   $after   Description
	 *
	 * @category              CategoryName
	 *
	 * @copyright  1997-2005 The PHP Group
	 * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
	 *
	 * @version    SVN: $Id$
	 *
	 * @global     string $gStr глобальная строчная переменная
	 *
	 * @see       http://pear.php.net/package/PackageName Указывает ссылку на документацию элемента.
	 * @since      1.2.0
	 * @deprecated 2.0.0
	 * @see        wp_signon()
	 */
	public function test( mixed $str, bool $ret, string $content, $after ): void {}

	/**
	 * QueryMonitor plugin.
	 *
	 * @since 2025.1
	 */
	public static function launch(): void
	{
		if ( ! Is::dashboard() ) {
			return;
		}

		Hook::add(
			'grafema_dashboard_footer',
			function () {
				?>
				<template x-teleport="#query">
					<a class="menu__link" x-show="query" href="#">
						<i class="ph ph-monitor"></i> <?php printf( '%s %s %sQ', Debug::timer( 'getall' ), Debug::memory_peak(), Db::queries() ); ?>
					</a>
				</template>
				<?php
			}
		);
	}

	public static function activate()
	{
		// TODO: Implement activate() method.
	}

	public static function deactivate()
	{
		// TODO: Implement deactivate() method.
	}

	public static function install()
	{
		// TODO: Implement install() method.
	}

	public static function uninstall()
	{
		// TODO: Implement uninstall() method.
	}
};
