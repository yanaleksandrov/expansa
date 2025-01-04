<?php

namespace Expansa\Http\Auth;

use Expansa\Http\Contracts\Auth;
use Expansa\Http\Exception\ArgumentCount;
use Expansa\Http\Exception\InvalidArgument;
use Expansa\Http\Hooks;

/**
 * Basic Authentication provider
 *
 * Provides a handler for Basic HTTP authentication via the Authorization
 * header.
 *
 * @package Requests\Authentication
 */
class Basic implements Auth
{
    /**
     * Username
     *
     * @var string
     */
    public string $user;

    /**
     * Password
     *
     * @var string
     */
    public string $pass;

    /**
     * Constructor

     * @param array|null $args Array of user and password. Must have exactly two elements
     *
     * @throws InvalidArgument When the passed argument is not an array or null.
     * @throws ArgumentCount   On incorrect number of array elements (`authbasicbadargs`).
     */
    public function __construct(?array $args = null)
    {
        if (is_array($args)) {
            if (count($args) !== 2) {
                throw ArgumentCount::create('an array with exactly two elements', count($args), 'authbasicbadargs');
            }

            list($this->user, $this->pass) = $args;
            return;
        }

        if ($args !== null) {
            throw InvalidArgument::create(1, '$args', 'array|null', gettype($args));
        }
    }

    /**
     * Register the necessary callbacks.
     *
     * @param Hooks $hooks Hook system
     */
    public function register(Hooks $hooks): void
    {
        $hooks->register('curl.before_send', [$this, 'curl_before_send']);
        $hooks->register('fsockopen.after_headers', [$this, 'fsockopen_header']);
    }

    /**
     * Set cURL parameters before the data is sent.
     *
     * @param resource|\CurlHandle $handle The cURL handle.
     */
    public function curl_before_send(&$handle): void
    {
        curl_setopt($handle, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($handle, CURLOPT_USERPWD, $this->getAuthString());
    }

    /**
     * Add extra headers to the request before sending.
     *
     * @param string $out HTTP header string.
     */
    public function fsockopen_header(&$out): void
    {
        $out .= sprintf("Authorization: Basic %s\r\n", base64_encode($this->getAuthString()));
    }

    /**
     * Get the authentication string (user:pass)
     *
     * @return string
     */
    public function getAuthString(): string
    {
        return $this->user . ':' . $this->pass;
    }
}
