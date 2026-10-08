<?php

declare(strict_types=1);

namespace App\Api\User;

use App\Models\Option;
use App\Models\User;
use Expansa\Auth\Exceptions\InvalidCredential;
use Expansa\Auth\Passkey as RelyingParty;
use Expansa\Auth\Passkey\Assertion;
use Expansa\Auth\Passkey\Attestation;
use Expansa\Auth\Passkey\Credential;
use Expansa\Facades\Base64;
use Expansa\Facades\Db;
use Expansa\Facades\Session;
use RuntimeException;

/**
 * Passkeys of site users: WebAuthn ceremonies of Expansa\Auth\Passkey bound to the site URL,
 * credentials in the `passkeys` table and pending challenges in the session.
 *
 * Challenges live in the session of the browser that started the ceremony and are removed
 * by the first check, so a response can't be replayed and a failed one can't be retried.
 */
final class Passkey
{
    /**
     * Lifetime of a pending challenge in seconds.
     */
    private const int CHALLENGE_TTL = 300;

    /**
     * Pending challenges kept per ceremony.
     */
    private const int MAX_PENDING = 5;

    /**
     * Columns a stored credential is restored from.
     */
    private const array COLUMNS = [
        'credential_id',
        'algorithm',
        'public_key',
        'counter',
        'transports',
        'backup_eligible',
        'backed_up',
    ];

    /**
     * Relying party of the site URL.
     */
    private readonly RelyingParty $rp;

    /**
     * Takes the RP ID and origin from the site URL.
     *
     * @throws RuntimeException If the site URL has no host.
     */
    public function __construct()
    {
        $url  = parse_url(url());
        $host = $url['host'] ?? throw new RuntimeException('The site URL must include a host.');

        $this->rp = new RelyingParty(
            id: $host,
            origin: ($url['scheme'] ?? 'https') . '://' . $host . (isset($url['port']) ? ':' . $url['port'] : ''),
            name: (string) Option::get('site.name', '') ?: 'Expansa',
        );
    }

    /**
     * Get the passkeys of a user for the profile, newest first.
     *
     * @param User $user Credentials owner.
     * @return array<int, array{id: int, name: string, backed_up: bool, created_at: string, used_at: ?string}>
     */
    public static function all(User $user): array
    {
        return Db::select('passkeys', ['id', 'name', 'backed_up [Bool]', 'created_at', 'used_at'], [
            'user_id' => $user->id,
            'ORDER'   => ['id' => 'DESC'],
        ]) ?? [];
    }

    /**
     * Remove a passkey of a user.
     *
     * @param User $user Credentials owner, a foreign ID removes nothing.
     * @param int  $id   Passkey ID.
     * @return bool      Whether the passkey was removed.
     */
    public static function delete(User $user, int $id): bool
    {
        return (Db::delete('passkeys', ['id' => $id, 'user_id' => $user->id])?->rowCount() ?? 0) > 0;
    }

    /**
     * Start registering a passkey for a signed-in user.
     * Registered credentials are excluded, so an authenticator isn't added twice.
     *
     * @param User $user
     * @return array<string, mixed> Creation options for the browser.
     */
    public function creationOptions(User $user): array
    {
        $exclude = array_map(
            fn (array $row) => $this->credential($row, $user),
            Db::select('passkeys', self::COLUMNS, ['user_id' => $user->id]) ?? []
        );

        return $this->rp->creationOptions(
            $this->challenge('create'),
            $user->uuid,
            $user->login,
            $user->showname ?: $user->login,
            $exclude,
        );
    }

    /**
     * Finish registering: verify the response and store the credential.
     *
     * @param User   $user
     * @param string $json Browser credential.
     * @param string $name Label shown in the profile.
     * @return array{id: int, name: string, backed_up: bool, created_at: string, used_at: null} Stored passkey.
     * @throws InvalidCredential|RuntimeException If the challenge expired or the response is invalid.
     */
    public function create(User $user, string $json, string $name): array
    {
        $attestation = Attestation::parse($json);
        $credential  = $this->rp->register($attestation, $this->consume('create', $attestation->challenge), $user->uuid);
        $name        = mb_substr(trim($name), 0, 100) ?: 'Passkey';

        Db::insert('passkeys', [
            'user_id'         => $user->id,
            'credential_id'   => Base64::encode($credential->id, true),
            'credential_hash' => hash('sha256', $credential->id),
            'algorithm'       => $credential->algorithm,
            'public_key'      => $credential->publicKey,
            'counter'         => $credential->counter,
            'transports'      => implode(',', $credential->transports),
            'backup_eligible' => (int) $credential->backupEligible,
            'backed_up'       => (int) $credential->backedUp,
            'name'            => $name,
        ]);

        return [
            'id'         => (int) Db::id(),
            'name'       => $name,
            'backed_up'  => $credential->backedUp,
            'created_at' => date('Y-m-d H:i:s'),
            'used_at'    => null,
        ];
    }

    /**
     * Start a sign-in; no login is needed, so the options reveal nothing about accounts.
     *
     * @return array<string, mixed> Request options for the browser.
     */
    public function requestOptions(): array
    {
        return $this->rp->requestOptions($this->challenge('request'));
    }

    /**
     * Finish a sign-in: find the credential, verify the signature and resolve its owner.
     * The new signature counter and backup state are saved, so a cloned authenticator is detected;
     * the counter only grows, so of two sign-ins with the same response state only one wins.
     *
     * @param string $json Browser credential.
     * @return User
     * @throws InvalidCredential|RuntimeException If the challenge expired, the credential is unknown or invalid.
     */
    public function verify(string $json): User
    {
        $assertion = Assertion::parse($json);
        $challenge = $this->consume('request', $assertion->challenge);

        $row  = Db::get('passkeys', ['id', 'user_id', ...self::COLUMNS], ['credential_hash' => hash('sha256', $assertion->id)]);
        $user = is_array($row) ? User::find((int) $row['user_id']) : null;
        if (! $user instanceof User) {
            throw new InvalidCredential('The credential is not registered.');
        }

        $credential = $this->rp->authenticate($assertion, $challenge, $this->credential($row, $user));
        $counted    = $credential->counter !== 0;

        // authenticators without a counter always send 0, there is nothing to race for
        $where   = $counted ? ['id' => $row['id'], 'counter[<]' => $credential->counter] : ['id' => $row['id']];
        $updated = Db::update('passkeys', [
            'counter'   => $credential->counter,
            'backed_up' => (int) $credential->backedUp,
            'used_at'   => date('Y-m-d H:i:s'),
        ], $where)?->rowCount() ?? 0;

        if ($counted && $updated === 0) {
            throw new InvalidCredential('A concurrent sign-in already used this signature counter.');
        }

        return $user;
    }

    /**
     * Create a challenge and add it to the pending ones of the ceremony in the session:
     * several may wait at once (tabs, the login autofill and the button), the oldest are dropped.
     *
     * @param string $ceremony `create` or `request`.
     * @return string Raw challenge.
     */
    private function challenge(string $ceremony): string
    {
        $challenge = random_bytes(32);
        $pending   = $this->pending($ceremony);

        $pending[bin2hex($challenge)] = time() + self::CHALLENGE_TTL;
        Session::set("passkey.$ceremony", array_slice($pending, -self::MAX_PENDING, preserve_keys: true));

        return $challenge;
    }

    /**
     * Take the challenge a response answers out of the pending ones; it is removed before
     * the check, so a response can't be replayed and a failed one can't be retried.
     *
     * @param string $ceremony  `create` or `request`.
     * @param string $challenge Raw challenge of the parsed response, not yet verified.
     * @return string The same challenge, now known to be issued to this session.
     * @throws RuntimeException If the challenge is unknown or expired.
     */
    private function consume(string $ceremony, string $challenge): string
    {
        $pending = $this->pending($ceremony);
        $key     = bin2hex($challenge);
        $found   = isset($pending[$key]);

        unset($pending[$key]);
        Session::set("passkey.$ceremony", $pending);

        if (! $found) {
            throw new RuntimeException('The passkey challenge has expired.');
        }

        return $challenge;
    }

    /**
     * Unexpired pending challenges of a ceremony, starting the session if needed.
     *
     * @param string $ceremony `create` or `request`.
     * @return array<string, int> Hex challenge => expiry timestamp.
     */
    private function pending(string $ceremony): array
    {
        if (! Session::isStarted()) {
            Session::start();
        }

        $now = time();

        return array_filter((array) Session::get("passkey.$ceremony", []), fn (mixed $expires) => is_int($expires) && $expires >= $now);
    }

    /**
     * Restore the stored credential of a passkey row.
     *
     * @param array<string, mixed> $row  Row with the COLUMNS.
     * @param User                 $user Owner, its UUID is the user handle.
     * @return Credential
     */
    private function credential(array $row, User $user): Credential
    {
        return new Credential(
            (string) Base64::decode($row['credential_id']),
            $user->uuid,
            (int) $row['algorithm'],
            $row['public_key'],
            (int) $row['counter'],
            array_values(array_filter(explode(',', $row['transports']))),
            (bool) $row['backup_eligible'],
            (bool) $row['backed_up'],
        );
    }
}
