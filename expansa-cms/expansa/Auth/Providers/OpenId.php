<?php

declare(strict_types=1);

namespace Expansa\Auth\Providers;

use Closure;
use Expansa\Auth\Exceptions\InvalidToken;
use Expansa\Auth\Exceptions\RequestFailed;
use Expansa\Auth\Internal\Jwt;
use Expansa\Auth\OAuth\Profile;
use Expansa\Auth\OAuth\State;

/**
 * Any OpenID Connect provider by its issuer URL: endpoints come from discovery, the user from
 * the identity token verified by the issuer's JWK set. Google is this provider with the issuer
 * `https://accounts.google.com`. Discovery and keys are fetched once per instance, so per sign-in.
 */
class OpenId extends AbstractProvider
{
    protected const array SCOPES = ['openid', 'email', 'profile'];

    /**
     * Seconds of clock difference allowed for the token expiry.
     */
    private const int LEEWAY = 60;

    /**
     * Discovery document of the issuer.
     *
     * @var array<string, mixed>|null
     */
    private ?array $configuration = null;

    public function __construct(
        string $name,
        string $clientId,
        string $clientSecret,
        string $redirect,

        /**
         * Issuer URL exactly as in its tokens, e.g. `https://accounts.google.com`.
         */
        public readonly string $issuer,

        /**
         * Requested scopes, `openid email profile` if empty.
         *
         * @var string[]
         */
        array $scopes = [],
        ?Closure $transport = null,
    ) {
        parent::__construct($name, $clientId, $clientSecret, $redirect, $scopes, $transport);
    }

    protected function getAuthorizeUrl(): string
    {
        return $this->getEndpoint('authorization_endpoint');
    }

    protected function getTokenUrl(): string
    {
        return $this->getEndpoint('token_endpoint');
    }

    protected function getRedirectParameters(State $state): array
    {
        return ['nonce' => $state->nonce];
    }

    protected function createProfile(array $token, State $state): Profile
    {
        if (! is_string($token['id_token'] ?? null)) {
            throw new InvalidToken('The provider returned no identity token.');
        }

        $keys   = $this->request('GET', $this->getEndpoint('jwks_uri'))['keys'] ?? null;
        $claims = Jwt::decode($token['id_token'], is_array($keys) ? $keys : []);

        $this->checkClaims($claims, $state);

        // the token may carry only the subject, the rest is behind the userinfo endpoint
        $userinfo = $this->configuration['userinfo_endpoint'] ?? null;
        if (! isset($claims['email']) && is_string($userinfo)) {
            $info = $this->request('GET', $userinfo, ['Authorization' => 'Bearer ' . $token['access_token']]);

            if (($info['sub'] ?? null) === $claims['sub']) {
                $claims = array_filter($claims, fn (mixed $value) => $value !== null) + $info;
            }
        }

        return new Profile(
            provider: $this->name,
            id: $claims['sub'],
            email: is_string($claims['email'] ?? null) ? $claims['email'] : null,
            emailVerified: in_array($claims['email_verified'] ?? false, [true, 'true'], true),
            name: is_string($claims['name'] ?? null) ? $claims['name'] : '',
            avatar: is_string($claims['picture'] ?? null) ? $claims['picture'] : null,
            raw: $claims,
        );
    }

    /**
     * Check that the token was issued by the issuer to this client for this attempt and is in force.
     *
     * @param array<string, mixed> $claims
     * @param State                $state
     * @return void
     * @throws InvalidToken If a claim does not match.
     */
    private function checkClaims(array $claims, State $state): void
    {
        $audience = (array) ($claims['aud'] ?? []);
        $issuer   = $claims['iss'] ?? null;

        if (! is_string($claims['sub'] ?? null) || $claims['sub'] === '') {
            throw new InvalidToken('The token has no subject.');
        }

        // Google still issues tokens with the bare host as the issuer
        if ($issuer !== $this->issuer && (! is_string($issuer) || 'https://' . $issuer !== $this->issuer)) {
            throw new InvalidToken('The token was issued by another issuer.');
        }

        $isAudience = in_array($this->clientId, $audience, true);
        if (! $isAudience || (count($audience) > 1 && ($claims['azp'] ?? null) !== $this->clientId)) {
            throw new InvalidToken('The token was issued to another client.');
        }

        if (! is_int($claims['exp'] ?? null) || $claims['exp'] + self::LEEWAY < time()) {
            throw new InvalidToken('The token has expired.');
        }

        if (! is_string($claims['nonce'] ?? null) || ! hash_equals($state->nonce, $claims['nonce'])) {
            throw new InvalidToken('The token belongs to another sign-in attempt.');
        }
    }

    /**
     * Get an endpoint from the discovery document, fetched on first use.
     *
     * @param string $name Discovery field, e.g. `token_endpoint`.
     * @return string
     * @throws RequestFailed If discovery fails, names another issuer or lacks the endpoint.
     */
    private function getEndpoint(string $name): string
    {
        if ($this->configuration === null) {
            $configuration = $this->request('GET', rtrim($this->issuer, '/') . '/.well-known/openid-configuration');

            if (($configuration['issuer'] ?? null) !== $this->issuer) {
                throw new RequestFailed('The discovery document belongs to another issuer.');
            }

            $this->configuration = $configuration;
        }

        $url = $this->configuration[$name] ?? null;
        if (! is_string($url) || ! str_starts_with($url, 'https://')) {
            throw new RequestFailed("The discovery document has no [$name].");
        }

        return $url;
    }
}
