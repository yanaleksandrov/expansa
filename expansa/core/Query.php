<?php
namespace Expansa;

/**
 * Query class.
 *
 * @since 2025.1
 */
final class Query {

	/**
	 * Query vars set by the user.
	 *
	 * @since 2025.1
	 * @var array
	 */
	public array $query;

	/**
	 * SQL for the database query.
	 *
	 * @since 2025.1
	 * @var string
	 */
	public string $request;

	/**
	 * Array of post objects or post IDs.
	 *
	 * @since 2025.1
	 */
	public array $posts;

	/**
	 * The current post.
	 *
	 * @since 2025.1
	 */
	public array $post;

	/**
	 * The current slug.
	 *
	 * @since 2025.1
	 */
	public string $slug;

	/**
	 * Signifies whether the current query is for a any post type.
	 *
	 * @since 2025.1
	 * @var bool
	 */
	public bool $isPost = false;

	/**
	 * Signifies whether the current query is for the site homepage.
	 *
	 * @since 2025.1
	 * @var bool
	 */
	public bool $isHome = false;

	/**
	 * Signifies whether the current query couldn't find anything.
	 *
	 * @since 2025.1
	 * @var bool
	 */
	public bool $is404 = false;

	/**
	 * Signifies whether the current query is for an administrative interface page.
	 *
	 * @since 2025.1
	 * @var bool
	 */
	public bool $isDashboard = false;

	/**
	 * Is sign in page.
	 *
	 * @since 2025.1
	 * @var bool
	 */
	public bool $isSignIn = false;

	/**
	 * Is sign up page.
	 *
	 * @since 2025.1
	 * @var bool
	 */
	public bool $isSignUp = false;

	/**
	 * Is sign up page.
	 *
	 * @since 2025.1
	 * @var bool
	 */
	public bool $isResetPassword = false;

	/**
	 * Is auth page.
	 *
	 * @since 2025.1
	 * @var bool
	 */
	public bool $isAuth = false;

	/**
	 * Check Expansa CMS is installed.
	 *
	 * @since 2025.1
	 * @var bool
	 */
	public bool $isInstalled = false;

	/**
	 * Is installation page.
	 *
	 * @since 2025.1
	 * @var bool
	 */
	public bool $isInstallation = false;

	/**
	 * Is ajax request.
	 *
	 * @since 2025.1
	 * @var bool
	 */
	public bool $isAjax = false;

	/**
	 * Is REST API endpoint.
	 *
	 * @since 2025.1
	 * @var bool
	 */
	public bool $isApi = false;

	/**
	 * Sets the value of a query variable.
	 *
	 * @param string $field
	 * @param mixed $value
	 * @since 2025.1
	 */
	public function set( string $field, mixed $value ): void {
		$this->$field = $value;

		// set is auth page
		if ( in_array( $field, [ 'isSignIn', 'isSignUp', 'isResetPassword' ], true ) && $value === true ) {
			$this->isAuth = true;
		}
	}
}
