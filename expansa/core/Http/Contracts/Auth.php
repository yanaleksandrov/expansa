<?php

namespace Expansa\Http\Contracts;

use Expansa\Http\Hooks;
use Expansa\Http\Requests;

/**
 * Authentication provider interface
 * Implement this interface to act as an authentication provider.
 * Parameters should be passed via the constructor where possible, as this
 * makes it much easier for users to use your provider.
 *
 * @package Expansa\Http
 */
interface Auth
{
    /**
     * Register hooks as needed
     * This method is called in {@see Requests::request()} when the user has set an instance
     * as the 'auth' option. Use this callback to register all the Ыhooks you'll need.
     *
     * @param Hooks $hooks Hook system
     */
    public function register(Hooks $hooks);
}
