<?php
/**
 * This file is part of Grafema CMS.
 *
 * @link     https://www.grafema.io
 * @contact  team@core.io
 * @license  https://github.com/grafema-team/grafema/LICENSE.md
 */

namespace Grafema;

use JsonException;

/**
 * Session class
 * Удобная оберка для стандартных сессий.
 *
 * TODO: migrate to https://github.com/odan/session/tree/c7afc83519a109dd45039662ea8dd8aea7675761
 */
final class Session
{
	/**
	 * @var int Время жизни сессии
	 */
	private static $lifetime = 86400 * 14;

	private static $cookieName = 'cID';

	private static $started = false;

	/**
	 * @var int TO DO: Сессия видима для всех поддоменов
	 */
	private static $subdomain = false;

	/**
	 * Проверяет, установленны ли Cookie сессии и правильные ли они.
	 */
	public static function isCreated()
	{
		return ( ! empty( $_COOKIE[self::$cookieName] ) and ctype_alnum( $_COOKIE[self::$cookieName] ) ) ? true : false;
	}

	/**
	 * Инициирует сессию. Перед любой попыткой вызвать метод ниже надо обязательно стартовать сессию!
	 * Если вы не уверены, была ли сессия начата - можете вызвать этот метод снова.
	 * Если сессия не была начата, метод ее начнет. Иначе - вызов будет проинорирован.
	 */
	public static function start( int $lifetime = 0 )
	{
		if ( ! self::$started ) {
			if ( self::isCreated() ) {
				unset( $_COOKIE[self::$cookieName] );
			}

			if ( ! self::$subdomain ) {
				session_set_cookie_params( $lifetime ?? self::$lifetime , '/' );
			}
			session_start();
			self::$started = true;
		}
	}

	/**
	 * Добавление или установка значений в сессию по ключу.
	 *
	 * @param string $key   Ключ, в который необходимо добавить значения
	 * @param string $value Значение добавления
	 */
	public static function set( $key, $value )
	{
		if ( self::$started ) {
			$_SESSION[$key] = Json::encode( $value );
		} else {
			trigger_error( 'You should start Session first', E_USER_WARNING );
		}
	}

	/**
	 * Получение значение сессии по ключу.
	 *
	 * @param string $key Ключ, по которому необходимо получить значения
	 *
	 * @return mixed|null Значение сессии по ключу
	 */
	public static function get( $key )
	{
		if ( self::$started ) {
			return isset( $_SESSION[$key] ) ? Json::decode( $_SESSION[$key] ) : null;
		}
		trigger_error( 'You should start Session first', E_USER_WARNING );
	}

	/**
	 * Удаление значения сессии по ключу.
	 *
	 * @param string $key Ключ, по которому необходимо удалить значения
	 */
	public static function unset( $key )
	{
		if ( self::$started ) {
			unset( $_SESSION[$key] );
		} else {
			trigger_error( 'You should start Session first', E_USER_WARNING );
		}
	}

	/**
	 * Удаление всех значений сессии
	 * Метод является оберткой для реализации стандартного метода.
	 *
	 * @see https://php.ru/manual/function.session-unset.html PHP session_unset
	 */
	public static function deleteAll()
	{
		if ( self::$started ) {
			session_unset();
		}
	}

	/**
	 * Очищает все значения, переданные сессии, но сессию не уничтожает.
	 */
	public static function clear()
	{
		if ( self::$started ) {
			unset( $_SESSION );
		} else {
			trigger_error( 'You should start Session first', E_USER_WARNING );
		}
	}

	/**
	 * Уничтожает сессию, при этом стираются Cookie, и все значения, переданные в сессию.
	 */
	public static function destroy()
	{
		if ( self::$started ) {
			self::$started = false;
			unset( $_COOKIE[self::$cookieName] );
			setcookie( self::$cookieName, '', 1, '/' );
			session_destroy();
		} else {
			trigger_error( 'Session is not started!', E_USER_WARNING );
		}
	}

	/**
	 * Уничтожает, а потом стартует сессию.
	 */
	public static function restart()
	{
		self::destroy();
		self::start();
	}

	/**
	 * Возвращает массив всех переменных, переданных в сессию.
	 * Рекомендуется использовать только для отладки, а все значения получать при помощи метода get.
	 *
	 * @return array
	 */
	public static function getArray()
	{
		if ( self::$started ) {
			return $_SESSION;
		}
		trigger_error( 'You should start Session first', E_USER_WARNING );
	}

	/**
	 * Сохраняет все значения и закрывает сессию.
	 * После вызова этого метода работа с сессией невозможна, пока сессия не будет снова запущенна методом start().
	 */
	public static function commit()
	{
		if ( self::$started ) {
			session_write_close();
			self::$started = false;
		} else {
			trigger_error( 'You should start Session first', E_USER_WARNING );
		}
	}

	/**
	 * Получение статуса активности сессии.
	 *
	 * @see https://secure.php.net/manual/en/function.session-status.php PHP session_status
	 *
	 * @return bool Статус активности сессии
	 */
	private function active()
	{
		return session_status() === PHP_SESSION_ACTIVE;
	}
}
