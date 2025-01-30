<?php

declare(strict_types=1);

namespace App;

/**
 * Query class.
 */
final class Query
{
    /**
     * Query vars set by the user.
     *
     * @var array
     */
    public array $query;

    /**
     * SQL for the database query.
     *
     * @var string
     */
    public string $request;

    /**
     * Array of post objects or post IDs.
     */
    public array $posts;

    /**
     * The current post.
     */
    public array $post;

    /**
     * The current slug.
     */
    public string $slug;

    /**
     * Signifies whether the current query is for a any post type.
     *
     * @var bool
     */
    public bool $isPost = false;

    /**
     * Signifies whether the current query is for the site homepage.
     *
     * @var bool
     */
    public bool $isHome = false;

    /**
     * Signifies whether the current query couldn't find anything.
     *
     * @var bool
     */
    public bool $is404 = false;

    /**
     * Signifies whether the current query is for an administrative interface page.
     *
     * @var bool
     */
    public bool $isDashboard = false;

    /**
     * Is sign in page.
     *
     * @var bool
     */
    public bool $isSignIn = false;

    /**
     * Is sign up page.
     *
     * @var bool
     */
    public bool $isSignUp = false;

    /**
     * Is sign up page.
     *
     * @var bool
     */
    public bool $isResetPassword = false;

    /**
     * Is auth page.
     *
     * @var bool
     */
    public bool $isAuth = false;

    /**
     * Check Expansa CMS is installed.
     *
     * @var bool
     */
    public bool $isInstalled = false;

    /**
     * Is installation page.
     *
     * @var bool
     */
    public bool $isInstallation = false;

    /**
     * Is ajax request.
     *
     * @var bool
     */
    public bool $isAjax = false;

    /**
     * Is REST API endpoint.
     *
     * @var bool
     */
    public bool $isApi = false;

    /**
     * Sets the value of a query variable.
     *
     * @param string $field
     * @param mixed $value
     */
    public function set(string $field, mixed $value): void
    {
        $this->$field = $value;

        // set is auth page
        if (in_array($field, [ 'isSignIn', 'isSignUp', 'isResetPassword' ], true) && $value === true) {
            $this->isAuth = true;
        }
    }
}
