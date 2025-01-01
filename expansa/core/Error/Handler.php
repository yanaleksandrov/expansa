<?php
namespace Expansa\Error;

/**
 * Errors class.
 *
 * A class whose task is to simplify error handling (messages) when working with Expansa.
 * In order to start working with a class, you need to create an instance of it, and then add or remove errors (messages) to it.
 * The class applies the $message and $data parameters.
 *
 * These are strings and in general they are similar, but they separated by logic:
 *   $message - is a message for the user.
 *   $data    - is an instruction for the developer.
 *
 * @since 2025.1
 */
class Handler {

	/**
	 * Errors list storage.
	 *
	 * @since 2025.1
	 * @var array
	 */
	protected static array $errors = [];

	/**
	 * Add an error or append additional message to an existing error.
	 *
	 * @param string|int $code      Errors code.
	 * @param string|array $message Error single message or array of messages.
	 * @since 2025.1
	 */
	protected function push( string|int $code, string|array $message = '' ): void {
		if ( is_array( $message ) ) {
			self::$errors[ $code ] = array_merge( self::$errors[$code] ?? [], $message );
		} else {
			self::$errors[ $code ][] = $message;
		}
	}

	/**
	 * Removes the specified error.
	 *
	 * This function removes all error messages associated with the specified
	 * error code, along with any error data for that code.
	 *
	 * @since 2025.1
	 *
	 * @param string|int $code Errors code.
	 */
	protected function remove( string|int $code ): void {
		if ( isset( self::$errors[ $code ] ) ) {
			unset( self::$errors[ $code ] );
		}
	}

	/**
	 * Retrieve all error codes.
	 *
	 * @since 2025.1
	 *
	 * @return array List of error codes, if available.
	 */
	protected function getErrorCodes(): array {
		if ( ! $this->hasError() ) {
			return [];
		}
		return array_keys( self::$errors );
	}

	/**
	 * Retrieve all error messages or error messages matching code.
	 *
	 * @since 2025.1
	 *
	 * @param string|int $code Optional. Retrieve messages matching code, if exists.
	 * @return array Errors strings on success, or empty array on failure (if using code parameter).
	 */
	public function getError( string|int $code = '' ): array {
		if ( empty( $code ) ) {
			return self::$errors;
		}
		return self::$errors[ $code ] ?? [];
	}

	/**
	 * Verify if the instance contains errors.
	 *
	 * @since 2025.1
	 *
	 * @return bool
	 */
	protected function hasError(): bool {
		return ! empty( self::$errors );
	}
}
