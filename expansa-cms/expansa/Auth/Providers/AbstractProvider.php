<?php

declare(strict_types=1);

namespace Expansa\Auth\Providers;

use Closure;
use Expansa\Auth\Contracts\Provider;
use Expansa\Auth\Exceptions\Denied;
use Expansa\Auth\Exceptions\InvalidState;
use Expansa\Auth\Exceptions\RequestFailed;
use Expansa\Auth\Internal\Http;
use Expansa\Auth\OAuth\Profile;
use Expansa\Auth\OAuth\State;
use Expansa\Codecs\Base64;
use JsonException;

/**
 * OAuth 2.0 authorization code flow with PKCE (S256), the base of the providers: builds the consent
 * URL, checks the callback against its State and exchanges the code. A provider adds its endpoints
 * and turns the token response into a Profile; tokens of the provider are not kept.
 *
 * ```php
 * final class Gitea extends AbstractProvider
 * {
 *     protected function getAuthorizeUrl(): string { return 'https://gitea.com/login/oauth/authorize'; }
 *     protected function getTokenUrl(): string { return 'https://gitea.com/login/oauth/access_token'; }
 *     protected function createProfile(array $token, State $state): Profile { ... }
 * }
 * ```
 */
abstract class AbstractProvider implements Provider
{
    /**
     * Scopes requested when the configuration gives none.
     *
     * @var string[]
     */
    protected const array SCOPES = [];

    /**
     * Sends requests to the provider:
     * `fn (string $method, string $url, array $headers, string $body): array{status: int, body: string}`.
     */
    protected Closure $transport;

    public function __construct(

        /**
         * Configured name, e.g. `google`.
         */
        public readonly string $name,

        /**
         * Client ID issued by the provider.
         */
        protected readonly string $clientId,

        /**
         * Client secret issued by the provider.
         */
        protected readonly string $clientSecret,

        /**
         * Callback URL registered at the provider.
         */
        protected readonly string $redirect,

        /**
         * Requested scopes, SCOPES if empty.
         *
         * @var string[]
         */
        protected readonly array $scopes = [],

        /**
         * Request sender, curl by default.
         */
        ?Closure $transport = null,
    ) {
        $this->transport = $transport ?? Http::send(...);
    }

    public function redirect(State $state): string
    {
        if ($state->provider !== $this->name) {
            throw new InvalidState("The sign-in attempt belongs to the [$state->provider] provider.");
        }

        return $this->getAuthorizeUrl() . '?' . http_build_query([
            'response_type'         => 'code',
            'client_id'             => $this->clientId,
            'redirect_uri'          => $this->redirect,
            'scope'                 => implode(' ', $this->scopes ?: static::SCOPES),
            'state'                 => $state->value,
            'code_challenge'        => new Base64()->encode(hash('sha256', $state->verifier, true), true),
            'code_challenge_method' => 'S256',
            ...$this->getRedirectParameters($state),
        ], '', '&', PHP_QUERY_RFC3986);
    }

    public function profile(array $query, State $state): Profile
    {
        if (
            $state->provider !== $this->name
            || ! is_string($query['state'] ?? null)
            || ! hash_equals($state->value, $query['state'])
            || $state->isExpired()
        ) {
            throw new InvalidState('The sign-in attempt is foreign or expired.');
        }

        if (isset($query['error'])) {
            $error = is_string($query['error']) ? $query['error'] : 'error';

            throw new Denied("The provider refused the sign-in: $error.");
        }

        if (! is_string($query['code'] ?? null) || $query['code'] === '') {
            throw new InvalidState('The callback has no authorization code.');
        }

        $body = http_build_query([
            'grant_type'    => 'authorization_code',
            'code'          => $query['code'],
            'redirect_uri'  => $this->redirect,
            'client_id'     => $this->clientId,
            'client_secret' => $this->clientSecret,
            'code_verifier' => $state->verifier,
        ]);
        $token = $this->request('POST', $this->getTokenUrl(), ['Content-Type' => 'application/x-www-form-urlencoded'], $body);

        // GitHub answers a bad code with 200 and an "error" field
        if (! is_string($token['access_token'] ?? null)) {
            $error = is_string($token['error'] ?? null) ? $token['error'] : 'no access token';

            throw new RequestFailed("The provider did not exchange the code: $error.");
        }

        return $this->createProfile($token, $state);
    }

    /**
     * URL of the consent page.
     *
     * @return string
     * @throws RequestFailed If it has to be discovered and the provider fails.
     */
    abstract protected function getAuthorizeUrl(): string;

    /**
     * URL the code is exchanged at.
     *
     * @return string
     * @throws RequestFailed If it has to be discovered and the provider fails.
     */
    abstract protected function getTokenUrl(): string;

    /**
     * Turn the token response into the profile of the user.
     *
     * @param array<string, mixed> $token Token response with `access_token`.
     * @param State                $state
     * @return Profile
     */
    abstract protected function createProfile(array $token, State $state): Profile;

    /**
     * Additional parameters of the consent URL.
     *
     * @param State $state
     * @return array<string, string>
     */
    protected function getRedirectParameters(State $state): array
    {
        return [];
    }

    /**
     * Send a request expecting a JSON object.
     *
     * @param string                $method
     * @param string                $url
     * @param array<string, string> $headers `Accept: application/json` is added.
     * @param string                $body
     * @return array<string, mixed>
     * @throws RequestFailed If the request fails, the status is not 2xx or the body is not a JSON object.
     */
    protected function request(string $method, string $url, array $headers = [], string $body = ''): array
    {
        $response = ($this->transport)($method, $url, $headers + ['Accept' => 'application/json'], $body);

        if ($response['status'] < 200 || $response['status'] > 299) {
            throw new RequestFailed("The provider answered with HTTP {$response['status']}.");
        }

        try {
            $data = json_decode($response['body'], true, 32, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new RequestFailed('The provider answered with invalid JSON.');
        }

        return is_array($data) ? $data : throw new RequestFailed('The provider answered with invalid JSON.');
    }
}
