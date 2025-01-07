<?php

namespace Expansa\Http\Proxy;

use CurlHandle;
use Expansa\Http\Exception\ArgumentCount;
use Expansa\Http\Hooks;
use Expansa\Http\Contracts\Proxy;

/**
 * HTTP Proxy connection interface
 * Provides a handler for connection via an HTTP proxy
 *
 * @package Expansa\Http
 */
final class Http implements Proxy
{
    /**
     * Proxy host and port
     *
     * Notation: "host:port" (eg 127.0.0.1:8080 or someproxy.com:3128)
     *
     * @var string
     */
    public string $proxy;

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
     * Do we need to authenticate? (ie username & password have been provided)
     *
     * @var bool
     */
    public bool $use_authentication;

    /**
     * Constructor
     *
     * @param array|string|null $args Proxy as a string or an array of proxy, user and password.
     *                                When passed as an array, must have exactly one (proxy)
     *                                or three elements (proxy, user, password).
     *
     * @throws ArgumentCount On incorrect number of arguments (`proxyhttpbadargs`)
     */
    public function __construct(array|string|null $args = null)
    {
        if (is_string($args)) {
            $this->proxy = $args;
        } elseif (is_array($args)) {
            if (count($args) === 1) {
                list($this->proxy) = $args;
            } elseif (count($args) === 3) {
                list($this->proxy, $this->user, $this->pass) = $args;
                $this->use_authentication                    = true;
            } else {
                throw ArgumentCount::create(
                    'an array with exactly one element or exactly three elements',
                    count($args),
                    'proxyhttpbadargs'
                );
            }
        }
    }

    /**
     * Register the necessary callbacks
     *
     * @param Hooks $hooks Hook system
     */
    public function register(Hooks $hooks): void
    {
        $hooks->register('curl.before_send', [$this, 'curlBeforeSend']);

        $hooks->register('fsockopen.remote_socket', [$this, 'fsockopenRemoteSocket']);
        $hooks->register('fsockopen.remote_host_path', [$this, 'fsockopenRemoteHostPath']);
        if ($this->use_authentication) {
            $hooks->register('fsockopen.after_headers', [$this, 'fsockopenHeader']);
        }
    }

    /**
     * Set cURL parameters before the data is sent
     *
     * @param CurlHandle $handle cURL handle
     */
    public function curlBeforeSend(CurlHandle &$handle): void
    {
        curl_setopt($handle, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
        curl_setopt($handle, CURLOPT_PROXY, $this->proxy);

        if ($this->use_authentication) {
            curl_setopt($handle, CURLOPT_PROXYAUTH, CURLAUTH_ANY);
            curl_setopt($handle, CURLOPT_PROXYUSERPWD, $this->getAuthString());
        }
    }

    /**
     * Alter remote socket information before opening socket connection
     *
     * @param string $remote_socket Socket connection string
     */
    public function fsockopenRemoteSocket(string &$remote_socket): void
    {
        $remote_socket = $this->proxy;
    }

    /**
     * Alter remote path before getting stream data
     *
     * @param string $path Path to send in HTTP request string ("GET ...")
     * @param string $url  Full URL we're requesting
     */
    public function fsockopenRemoteHostPath(string &$path, string $url): void
    {
        $path = $url;
    }

    /**
     * Add extra headers to the request before sending
     *
     * @param string $out HTTP header string
     */
    public function fsockopenHeader(string &$out): void
    {
        $out .= sprintf("Proxy-Authorization: Basic %s\r\n", base64_encode($this->getAuthString()));
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
