<?php

declare(strict_types=1);

use Expansa\Auth\Contracts\Identity;
use Expansa\Auth\Contracts\Sessions;
use Expansa\Auth\Exceptions\Denied;
use Expansa\Auth\Exceptions\InvalidCredential;
use Expansa\Auth\Exceptions\InvalidState;
use Expansa\Auth\Exceptions\InvalidToken;
use Expansa\Auth\Exceptions\ProviderNotFound;
use Expansa\Auth\Exceptions\RequestFailed;
use Expansa\Auth\Exceptions\TooManyAttempts;
use Expansa\Auth\Manager;
use Expansa\Auth\OAuth\State;
use Expansa\Auth\Passkey;
use Expansa\Auth\Passkey\Assertion;
use Expansa\Auth\Passkey\Attestation;
use Expansa\Auth\Passkey\Credential;
use Expansa\Auth\Providers\GitHub;
use Expansa\Auth\Providers\OpenId;
use Expansa\Facades\Auth;

// run: php tests/Auth.php
require_once __DIR__ . '/bootstrap.php';

/**
 * Minimal CBOR encoder for the authenticator emulation: ints, byte strings, text keys, maps.
 *
 * @param mixed $value Binary strings are marked as ['bytes' => ...].
 * @return string
 */
function cbor(mixed $value): string
{
    $head = static function (int $major, int $argument): string {
        return match (true) {
            $argument < 24     => chr($major << 5 | $argument),
            $argument < 0x100  => chr($major << 5 | 24) . chr($argument),
            $argument < 0x10000 => chr($major << 5 | 25) . pack('n', $argument),
            default            => chr($major << 5 | 26) . pack('N', $argument),
        };
    };

    return match (true) {
        is_int($value)       => $value >= 0 ? $head(0, $value) : $head(1, -1 - $value),
        is_string($value)    => $head(3, strlen($value)) . $value,
        isset($value['bytes']) && count($value) === 1 => $head(2, strlen($value['bytes'])) . $value['bytes'],
        default              => $head(5, count($value)) . implode('', array_map(
            fn ($key, $item) => cbor($key) . cbor($item),
            array_keys($value),
            $value
        )),
    };
}

/**
 * Unpadded base64url.
 *
 * @param string $bytes
 * @return string
 */
function b64(string $bytes): string
{
    return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
}

/**
 * Emulated authenticator holding one key pair.
 */
final class Authenticator
{
    public int $counter = 0;

    public string $credentialId;

    public function __construct(public string $privateKey, public int $algorithm)
    {
        $this->credentialId = random_bytes(16);
    }

    public function cose(): array
    {
        $details = openssl_pkey_get_details(openssl_pkey_get_private($this->privateKey));

        return $this->algorithm === -7
            ? [1 => 2, 3 => -7, -1 => 1, -2 => ['bytes' => $details['ec']['x']], -3 => ['bytes' => $details['ec']['y']]]
            : [1 => 3, 3 => -257, -1 => ['bytes' => $details['rsa']['n']], -2 => ['bytes' => $details['rsa']['e']]];
    }

    public function authData(string $rpId, int $flags, bool $attested, array $extensions = []): string
    {
        $data = hash('sha256', $rpId, true) . chr($flags) . pack('N', $this->counter);

        if ($attested) {
            $data .= str_repeat("\0", 16) . pack('n', strlen($this->credentialId)) . $this->credentialId . cbor($this->cose());
        }

        return $extensions ? $data . cbor($extensions) : $data;
    }

    public function create(string $rpId, string $origin, string $challenge, int $flags = 0x45): array
    {
        $clientData = json_encode(['type' => 'webauthn.create', 'challenge' => b64($challenge), 'origin' => $origin]);

        return [
            'id'       => b64($this->credentialId),
            'rawId'    => b64($this->credentialId),
            'type'     => 'public-key',
            'response' => [
                'clientDataJSON'    => b64($clientData),
                'attestationObject' => b64(cbor(['fmt' => 'none', 'attStmt' => [], 'authData' => ['bytes' => $this->authData($rpId, $flags, true)]])),
                'transports'        => ['internal'],
            ],
        ];
    }

    public function get(string $rpId, string $origin, string $challenge, string $userHandle, int $flags = 0x05, array $extensions = []): array
    {
        $this->counter++;
        $clientData = json_encode(['type' => 'webauthn.get', 'challenge' => b64($challenge), 'origin' => $origin]);
        $authData   = $this->authData($rpId, $flags, false, $extensions);
        openssl_sign($authData . hash('sha256', $clientData, true), $signature, $this->privateKey, OPENSSL_ALGO_SHA256);

        return [
            'id'       => b64($this->credentialId),
            'rawId'    => b64($this->credentialId),
            'type'     => 'public-key',
            'response' => [
                'clientDataJSON'    => b64($clientData),
                'authenticatorData' => b64($authData),
                'signature'         => b64($signature),
                'userHandle'        => b64($userHandle),
            ],
        ];
    }
}

/**
 * Parse an emulated registration response the way the API receives it.
 *
 * @param array $response
 * @return Attestation
 */
function attestation(array $response): Attestation
{
    return Attestation::parse(json_encode($response));
}

/**
 * Parse an emulated sign-in response the way the API receives it.
 *
 * @param array $response
 * @return Assertion
 */
function assertion(array $response): Assertion
{
    return Assertion::parse(json_encode($response));
}

$rp     = new Passkey('example.com', 'https://example.com', 'Example');
$handle = 'f47ac10b-58cc-4372-a567-0e02b2c3d479';
$old    = new Credential('old-id', $handle, -7, 'pem', transports: ['internal', 'hybrid']);

$options = $rp->creationOptions('challenge-bytes', $handle, 'admin', 'Admin', [$old]);
check('creation options require a discoverable credential', $options['authenticatorSelection'] === ['residentKey' => 'required', 'requireResidentKey' => true, 'userVerification' => 'preferred']);
check('creation options encode binary fields as base64url', $options['challenge'] === b64('challenge-bytes') && $options['user']['id'] === b64($handle) && $options['excludeCredentials'][0]['id'] === b64('old-id'));
check('excluded credentials carry their transports', $options['excludeCredentials'][0]['transports'] === ['internal', 'hybrid']);
check('request options list no credentials', $rp->requestOptions('c')['allowCredentials'] === [] && $rp->requestOptions('c')['rpId'] === 'example.com');
check('both ceremonies hint the device authenticator', $options['hints'] === ['client-device'] && $rp->requestOptions('c')['hints'] === ['client-device']);

foreach (['ES256' => [__DIR__ . '/fixtures/auth/es256.pem', -7], 'RS256' => [__DIR__ . '/fixtures/auth/rs256.pem', -257]] as $name => [$file, $algorithm]) {
    $authenticator = new Authenticator(file_get_contents($file), $algorithm);

    $challenge   = random_bytes(32);
    $attestation = attestation($authenticator->create('example.com', 'https://example.com', $challenge));
    check("$name: Attestation::parse() reads the ID and challenge", $attestation->id === $authenticator->credentialId && $attestation->challenge === $challenge);

    $credential = $rp->register($attestation, $challenge, $handle);
    check("$name: register() returns the credential", $credential->id === $authenticator->credentialId && $credential->userHandle === $handle && $credential->algorithm === $algorithm && $credential->transports === ['internal']);
    check("$name: the public key is valid PEM", openssl_pkey_get_public($credential->publicKey) !== false);

    $challenge = random_bytes(32);
    $response  = assertion($authenticator->get('example.com', 'https://example.com', $challenge, $handle));
    check("$name: Assertion::parse() reads the ID, challenge and user handle", $response->id === $authenticator->credentialId && $response->challenge === $challenge && $response->userHandle === $handle);

    $stored = $rp->authenticate($response, $challenge, $credential);
    check("$name: authenticate() returns the credential with the new counter", $stored->counter === 1 && $stored->id === $credential->id && $stored->publicKey === $credential->publicKey);
    check("$name: a replayed counter is rejected", throws(fn () => $rp->authenticate($response, $challenge, $stored), InvalidCredential::class));

    $challenge = random_bytes(32);
    $raw       = $authenticator->get('example.com', 'https://example.com', $challenge, $handle);
    $response  = assertion($raw);
    check("$name: another challenge is rejected", throws(fn () => $rp->authenticate($response, random_bytes(32), $stored), InvalidCredential::class));
    check("$name: another user handle is rejected", throws(fn () => $rp->authenticate($response, $challenge, new Credential($stored->id, 'other', $stored->algorithm, $stored->publicKey, 1)), InvalidCredential::class));
    check("$name: a changed backup eligibility is rejected", throws(fn () => $rp->authenticate($response, $challenge, new Credential($stored->id, $handle, $stored->algorithm, $stored->publicKey, 1, backupEligible: true)), InvalidCredential::class));

    $tampered = $raw;
    $tampered['response']['signature'] = b64(random_bytes(64));
    check("$name: a forged signature is rejected", throws(fn () => $rp->authenticate(assertion($tampered), $challenge, $stored), InvalidCredential::class));
    check("$name: a valid response passes", $rp->authenticate($response, $challenge, $stored)->counter === 2);
}

$authenticator = new Authenticator(file_get_contents(__DIR__ . '/fixtures/auth/es256.pem'), -7);
$challenge     = random_bytes(32);
$register      = fn (string $rpId, string $origin, int $flags = 0x45, ?Passkey $party = null) => ($party ?? $rp)->register(attestation($authenticator->create($rpId, $origin, $challenge, $flags)), $challenge, $handle);

check('a foreign origin is rejected', throws(fn () => $register('example.com', 'https://evil.com'), InvalidCredential::class));
check('a foreign RP ID is rejected', throws(fn () => $register('evil.com', 'https://example.com'), InvalidCredential::class));
check('user verification is only preferred by default', $rp->requestOptions('c')['userVerification'] === 'preferred' && $register('example.com', 'https://example.com', 0x41) instanceof Credential);
check('a missing user presence is rejected', throws(fn () => $register('example.com', 'https://example.com', 0x44), InvalidCredential::class));
check('a synced passkey keeps its backup flags', ($synced = $register('example.com', 'https://example.com', 0x5d))->backupEligible && $synced->backedUp);
check('a backup without eligibility is rejected', throws(fn () => $register('example.com', 'https://example.com', 0x55), InvalidCredential::class));

$strict = new Passkey('example.com', 'https://example.com', 'Example', requireUserVerification: true);
check('required user verification is announced', $strict->creationOptions('c', $handle, 'a', 'A')['authenticatorSelection']['userVerification'] === 'required');
check('a missing required user verification is rejected', throws(fn () => $register('example.com', 'https://example.com', 0x41, $strict), InvalidCredential::class));

$response = $authenticator->create('example.com', 'https://example.com', $challenge);
$response['response']['clientDataJSON'] = b64(json_encode(['type' => 'webauthn.get', 'challenge' => b64($challenge), 'origin' => 'https://example.com']));
check('another ceremony type is rejected', throws(fn () => $rp->register(attestation($response), $challenge, $handle), InvalidCredential::class));

$response = $authenticator->create('example.com', 'https://example.com', $challenge);
$response['rawId'] = b64('other-id');
check('an attested ID other than rawId is rejected', throws(fn () => attestation($response), InvalidCredential::class));

$response = $authenticator->create('example.com', 'https://example.com', $challenge);
$response['response']['attestationObject'] = b64("\xbf\xff");
check('malformed CBOR is rejected', throws(fn () => attestation($response), InvalidCredential::class));
check('a credential without rawId is rejected', throws(fn () => Assertion::parse('{"type":"public-key","response":{}}'), InvalidCredential::class));
check('a non-JSON credential is rejected', throws(fn () => Assertion::parse('nope'), InvalidCredential::class));

$response = $authenticator->get('example.com', 'https://example.com', $challenge, $handle);
$response['response']['authenticatorData'] = b64($authenticator->authData('example.com', 0x05, false) . "\0");
check('authenticator data with trailing bytes is rejected', throws(fn () => assertion($response), InvalidCredential::class));

$response = $authenticator->get('example.com', 'https://example.com', $challenge, $handle, 0x85, ['credProtect' => 1]);
check('authenticator extensions are accepted', assertion($response)->authenticatorData->counter === $authenticator->counter);

$response = $authenticator->get('example.com', 'https://example.com', $challenge, $handle);
$response['response']['userHandle'] = null;
check('a missing user handle parses as empty and is rejected', assertion($response)->userHandle === '' && throws(fn () => $rp->authenticate(assertion($response), $challenge, $synced), InvalidCredential::class));

// signed-in user
final class Member implements Identity
{
    public function __construct(public string $identifier, public string $stamp) {}
}


$members = ['admin' => new Member('admin', 'hash-1'), 'a|b' => new Member('a|b', 'hash-2')];
$cookies = ['auth' => '', 'device' => ''];
$sent    = [];
$finds   = 0;
$store   = [];
$connect = function (Manager $auth, int $maxAttempts = 0, ?Sessions $sessions = null) use (&$cookies, &$sent, &$finds, &$members, &$store): Manager {
    $auth->configure(
        find: function (string $identifier) use (&$finds, &$members): ?Member {
            $finds++;

            return $members[$identifier] ?? null;
        },
        key: 'secret',
        read: function (string $name) use (&$cookies): string {
            return $cookies[$name] ?? '';
        },
        write: function (string $name, string $value, int $expires) use (&$cookies, &$sent): void {
            $cookies[$name] = $value;
            $sent[]         = [$name, $expires];
        },
        maxAttempts: $maxAttempts,
        maxIpAttempts: 4,
        lockout: 600,
        readAttempts: function (string $key) use (&$store): ?array {
            return $store[$key] ?? null;
        },
        writeAttempts: function (string $key, ?array $attempts, int $ttl) use (&$store): void {
            if ($attempts === null) {
                unset($store[$key]);
            } else {
                $store[$key] = $attempts;
            }
        },
        sessions: $sessions,
    );

    return $auth;
};
$lastSent = function (string $name) use (&$sent): ?int {
    $found = array_filter($sent, fn (array $item) => $item[0] === $name);

    return $found === [] ? null : end($found)[1];
};

$auth = new Manager();
check('without configuration everyone is a guest', $auth->user() === null && ! $auth->isLoggedIn());
check('login() without configuration throws', throws(fn () => $auth->login($members['admin']), LogicException::class));

$auth = $connect(new Manager());
check('a request without a token is a guest and writes nothing', $auth->user() === null && $sent === []);

$auth->login($members['admin']);
[$identifier, $expires, $session, $signature] = explode('|', $cookies['auth']);
check('login() issues a session token for the lifetime', $identifier === 'admin' && $lastSent('auth') === 0 && abs((int) $expires - time() - 172800) <= 1);
check('the token is identifier|expires|session|hmac signed with the stamp', $session === '' && hash_equals(hash_hmac('sha256', "admin|$expires||hash-1", 'secret'), $signature));
check('login() makes the user current', $auth->user() === $members['admin'] && $auth->isLoggedIn());
check('login() trusts the device for a year', abs($lastSent('device') - time() - 31536000) <= 1 && $auth->isTrustedDevice('admin') && ! $auth->isTrustedDevice('a|b'));

$finds = 0;
$auth  = $connect(new Manager());
check('the next request finds the user by the token once', $auth->user() === $members['admin'] && $auth->user() === $members['admin'] && $finds === 1);

$auth->login($members['admin'], remember: true);
check('"remember me" keeps the token across browser restarts', $lastSent('auth') === (int) explode('|', $cookies['auth'])[1] && abs($lastSent('auth') - time() - 1209600) <= 1);

$members['admin']->stamp = 'hash-new';
$auth->refresh($members['admin']);
check('refresh() re-signs the token with the new stamp and the same expiry', $lastSent('auth') === (int) explode('|', $cookies['auth'])[1] && $connect(new Manager())->user() === $members['admin']);
check('a new password keeps the device trusted', $connect(new Manager())->isTrustedDevice('admin'));

$count = count($sent);
$auth->refresh($members['a|b']);
check('refresh() of another user sends nothing', count($sent) === $count);

$valid           = $cookies['auth'];
$cookies['auth'] = substr($valid, 0, -1) . (str_ends_with($valid, '0') ? '1' : '0');
check('a forged token is removed', $connect(new Manager())->user() === null && $cookies['auth'] === '');

$cookies['auth'] = $cookies['device'];
check('a device token does not sign in', $connect(new Manager())->user() === null);

$cookies['auth'] = $valid;
$members['admin']->stamp = 'hash-other';
check('a new stamp signs out every device', $connect(new Manager())->user() === null);

$members['admin']->stamp = 'hash-1';
$cookies['auth'] = 'admin|' . (time() - 1) . '||' . hash_hmac('sha256', 'admin|' . (time() - 1) . '||hash-1', 'secret');
check('an expired token is removed', $connect(new Manager())->user() === null && $cookies['auth'] === '');

$auth = $connect(new Manager());
$auth->login($members['a|b']);
check('an identifier with "|" survives the token', $connect(new Manager())->user() === $members['a|b']);

$auth->logout();
check('logout() removes the token and keeps the device trusted', $cookies['auth'] === '' && $lastSent('auth') === 0 && $auth->user() === null && $auth->isTrustedDevice('a|b'));

$auth->login(new Member('ghost', 'x'));
check('the token of a removed user is removed', $connect(new Manager())->user() === null && $cookies['auth'] === '');

Auth::swap($connect(new Manager()));
Auth::login($members['admin']);
check('the facade passes calls to the manager', Auth::user() === $members['admin'] && Auth::isLoggedIn());

// switching accounts
$members += ['editor' => new Member('editor', 'hash-3'), 'author' => new Member('author', 'hash-4')];
$cookies  = ['auth' => '', 'device' => '', 'accounts' => ''];
$accountsOf = fn (Manager $auth) => array_map(fn (Member $member) => $member->identifier, $auth->getAccounts());

$auth = $connect(new Manager());
$auth->login($members['admin'], remember: true);
check('a single account has no others', $accountsOf($auth) === [] && $cookies['accounts'] === '');

$auth->login($members['editor'], remember: true);
check('signing in to another account keeps the previous one', $auth->user() === $members['editor'] && $accountsOf($auth) === ['admin']);
check('the next request sees both accounts', $connect(new Manager())->user() === $members['editor'] && $accountsOf($connect(new Manager())) === ['admin']);
check('remembered accounts survive a browser restart', $lastSent('accounts') > time());

$auth = $connect(new Manager());
check('switchAccount() makes the account current', $auth->switchAccount('admin') && $auth->user() === $members['admin'] && $accountsOf($auth) === ['editor']);
check('the switch lasts to the next request', $connect(new Manager())->user() === $members['admin'] && $accountsOf($connect(new Manager())) === ['editor']);
check('an account that is not signed in can not be switched to', ! $connect(new Manager())->switchAccount('author') && $connect(new Manager())->user() === $members['admin']);

$auth = $connect(new Manager());
$auth->login($members['author']);
check('the most recent account comes first', $accountsOf($auth) === ['admin', 'editor']);

$auth->login($members['admin'], remember: true);
check('signing in again to a kept account does not duplicate it', $auth->user() === $members['admin'] && $accountsOf($auth) === ['author', 'editor']);
check('a kept session token makes the accounts cookie end with the browser', $lastSent('accounts') === 0);

$members['editor']->stamp = 'hash-3-new';
$auth = $connect(new Manager());
check('an account whose password changed is not offered', $accountsOf($auth) === ['author'] && ! $auth->switchAccount('editor'));
check('a broken account is dropped from the cookie', ! str_contains($cookies['accounts'], 'editor'));

$auth = $connect(new Manager());
$auth->logout();
check('logout() signs out the current account and switches to the next', $auth->user() === $members['author'] && $accountsOf($auth) === []);
check('the switch after logout() lasts', $connect(new Manager())->user() === $members['author']);

$auth = $connect(new Manager());
$auth->login($members['admin']);
$auth->logout(all: true);
check('logout(all: true) signs out every account', $auth->user() === null && $cookies['auth'] === '' && $cookies['accounts'] === '' && $connect(new Manager())->user() === null);

$cookies['accounts'] = json_encode(['garbage', 42]);
check('a malformed accounts cookie is ignored', $accountsOf($connect(new Manager())) === []);

// sessions
final class MemorySessions implements Sessions
{
    public array $records = [];

    public function create(Identity $user, int $expires): string
    {
        $id = bin2hex(random_bytes(8));
        $this->records[$id] = $user->identifier;

        return $id;
    }

    public function isActive(string $id, Identity $user): bool
    {
        return ($this->records[$id] ?? null) === $user->identifier;
    }

    public function delete(string $id): void
    {
        unset($this->records[$id]);
    }
}

$sessions = new MemorySessions();
$cookies  = ['auth' => '', 'device' => '', 'accounts' => ''];
$session  = fn (Manager $auth) => $connect($auth, sessions: $sessions);

$auth = $session(new Manager());
$auth->login($members['admin']);
$id = $auth->getSessionId();
check('login() records a session and puts its ID into the token', $id !== '' && isset($sessions->records[$id]) && explode('|', $cookies['auth'])[2] === $id);
check('a token with a live session signs in', $session(new Manager())->user() === $members['admin'] && $session(new Manager())->getSessionId() === $id);

$auth->refresh($members['admin']);
check('refresh() keeps the session', $auth->getSessionId() === $id && count($sessions->records) === 1);

$sessions->delete($id);
check('deleting the session signs the device out', $session(new Manager())->user() === null && $cookies['auth'] === '');

$auth = $session(new Manager());
$auth->login($members['admin']);
$cookies['auth'] = Expansa\Auth\Internal\Token::sign('admin', time() + 600, '', 'hash-1', 'secret');
check('with sessions a token without one does not sign in', $session(new Manager())->user() === null);

$sessions->records = [];
$cookies           = ['auth' => '', 'device' => '', 'accounts' => ''];
$auth              = $session(new Manager());
$auth->login($members['admin']);
$auth->login($members['author']);
$auth->logout();
check('logout() deletes the session of the account', count($sessions->records) === 1 && $auth->user() === $members['admin']);

$auth->logout(all: true);
check('logout(all: true) deletes every session of the browser', $sessions->records === []);

// throttling
$runs = 0;
$fail = function () use (&$runs): bool {
    $runs++;

    return false;
};
$lockedFor = function (Closure $attempt): int {
    try {
        $attempt();
    } catch (TooManyAttempts $e) {
        return $e->retryAfter;
    }

    return 0;
};

check('without a limit attempt() only runs the check', new Manager()->attempt('admin', '1.2.3.4', fn () => true) && ! new Manager()->attempt('admin', '1.2.3.4', fn () => false));

$cookies = ['auth' => '', 'device' => ''];
$store   = [];
$auth    = $connect(new Manager(), maxAttempts: 3);
check('failed attempts below the limit return false', ! $auth->attempt('admin', '1.2.3.4', $fail) && ! $auth->attempt('admin', '1.2.3.4', $fail) && ! $auth->attempt('admin', '1.2.3.4', $fail));
check('the store gets hashes, not the login or IP', array_keys($store) === [hash('sha256', 'login:admin'), hash('sha256', 'ip:1.2.3.4')]);

$runs = 0;
$wait = $lockedFor(fn () => $auth->attempt('admin', '1.2.3.4', fn () => true));
check('a locked login throws with the time to wait and skips the check', $wait > 590 && $wait <= 600 && $runs === 0);
check('an unknown device is locked from any IP and in any case', $lockedFor(fn () => $auth->attempt('ADMIN', '9.9.9.9', fn () => true)) > 0);
check('another login is not locked', $auth->attempt('editor', '5.6.7.8', fn () => true));

$owner = $connect(new Manager(), maxAttempts: 3);
$owner->login($members['admin']);
check('a trusted device of the owner is not locked by others', $owner->attempt('admin', '1.2.3.4', fn () => true));

$owner->attempt('admin', '1.2.3.4', $fail);
$owner->attempt('admin', '1.2.3.4', $fail);
$owner->attempt('admin', '1.2.3.4', $fail);
check('a trusted device has its own limit', $lockedFor(fn () => $owner->attempt('admin', '1.2.3.4', fn () => true)) > 0);

$cookies = ['auth' => '', 'device' => ''];
$store   = [];
$auth    = $connect(new Manager(), maxAttempts: 3);
foreach (['u1', 'u2', 'u3', 'u4'] as $login) {
    $auth->attempt($login, '7.7.7.7', $fail);
}
check('the IP limit stops one password tried for many logins', $lockedFor(fn () => $auth->attempt('u5', '7.7.7.7', fn () => true)) > 0 && $auth->attempt('u5', '8.8.8.8', fn () => true));

$auth->attempt('u1', '6.6.6.6', $fail);
$auth->attempt('u1', '6.6.6.6', fn () => true);
check('a success forgets the failures of the login', ! isset($store[hash('sha256', 'login:u1')]));
check('a success keeps the failures of the IP', isset($store[hash('sha256', 'ip:6.6.6.6')]));

$key = hash('sha256', 'login:admin');
$store[$key]['until'] = time() - 1;
check('the lockout ends after its time', $auth->attempt('admin', '2.2.2.2', fn () => true));

$store[$key] = ['count' => 0, 'lockouts' => 1, 'until' => time() - 1, 'last' => time() - 700];
$auth->attempt('admin', '3.3.3.3', $fail);
$auth->attempt('admin', '3.3.3.4', $fail);
$auth->attempt('admin', '3.3.3.5', $fail);
check('each next lockout is twice as long', $store[$key]['lockouts'] === 2 && abs($store[$key]['until'] - time() - 1200) <= 1);

$store[$key] = ['count' => 0, 'lockouts' => 20, 'until' => time() - 1, 'last' => time() - 700];
$auth->attempt('admin', '3.3.3.6', $fail);
$auth->attempt('admin', '3.3.3.7', $fail);
$auth->attempt('admin', '3.3.3.8', $fail);
check('a lockout lasts a day at most', abs($store[$key]['until'] - time() - 86400) <= 1);

$store[$key] = ['count' => 2, 'lockouts' => 3, 'until' => time() - 86500, 'last' => time() - 86500];
$auth->attempt('admin', '4.4.4.4', $fail);
check('a calm day forgets past lockouts and failures', $store[$key]['lockouts'] === 0 && $store[$key]['count'] === 1);

$store[$key] = ['count' => 2, 'lockouts' => 0, 'until' => 0, 'last' => time() - 601];
$auth->attempt('admin', '4.4.4.5', $fail);
check('failures further apart than the lockout do not add up', $store[$key]['count'] === 1);


// request limits
$store = [];
$auth  = $connect(new Manager());
$hits  = function (int $count) use ($auth): int {
    $passed = 0;
    for ($i = 0; $i < $count; $i++) {
        try {
            $auth->limit('reset:1.2.3.4', 3, 600);
            $passed++;
        } catch (TooManyAttempts) {
        }
    }

    return $passed;
};

check('without a store limit() lets everything through', $hits(0) === 0 && (function () {
    $plain = new Manager();
    for ($i = 0; $i < 10; $i++) {
        $plain->limit('k', 1, 60);
    }

    return true;
})());
check('limit() lets $maxAttempts requests through and stops the next ones', $hits(5) === 3);
check('limit() counts every request, the store gets a hash', array_keys($store) === [hash('sha256', 'limit:reset:1.2.3.4')]);

try {
    $auth->limit('reset:1.2.3.4', 3, 600);
    $wait = 0;
} catch (TooManyAttempts $e) {
    $wait = $e->retryAfter;
}
check('the wait is the rest of the window', $wait > 590 && $wait <= 600);
check('another key has its own window', (function () use ($auth) {
    $auth->limit('reset:5.6.7.8', 3, 600);

    return true;
})());

$store[hash('sha256', 'limit:reset:1.2.3.4')]['until'] = time() - 1;
check('a new window starts after the old one', $hits(3) === 3);
// sign-in providers
/**
 * Compact JWS of the claims signed by a private key.
 *
 * @param array<string, mixed> $claims
 * @param string               $pem    Private key.
 * @param array<string, mixed> $header
 * @return string
 */
function jwt(array $claims, string $pem, array $header): string
{
    $input = b64(json_encode($header)) . '.' . b64(json_encode($claims));
    openssl_sign($input, $signature, $pem, OPENSSL_ALGO_SHA256);

    // JWS keeps ECDSA as r || s, OpenSSL gives DER
    if (($header['alg'] ?? '') === 'ES256') {
        $length    = ord($signature[3]);
        $signature = str_pad(ltrim(substr($signature, 4, $length), "\0"), 32, "\0", STR_PAD_LEFT)
            . str_pad(ltrim(substr($signature, 6 + $length), "\0"), 32, "\0", STR_PAD_LEFT);
    }

    return $input . '.' . b64($signature);
}

/**
 * Public JWK of a private key.
 *
 * @param string $pem
 * @param string $kid
 * @return array<string, string>
 */
function jwk(string $pem, string $kid): array
{
    $details = openssl_pkey_get_details(openssl_pkey_get_private($pem));

    return isset($details['rsa'])
        ? ['kty' => 'RSA', 'kid' => $kid, 'use' => 'sig', 'alg' => 'RS256', 'n' => b64($details['rsa']['n']), 'e' => b64($details['rsa']['e'])]
        : ['kty' => 'EC', 'kid' => $kid, 'crv' => 'P-256', 'x' => b64($details['ec']['x']), 'y' => b64($details['ec']['y'])];
}

$routes    = [];
$requests  = [];
$transport = function (string $method, string $url, array $headers, string $body) use (&$routes, &$requests): array {
    $requests[] = ['method' => $method, 'url' => $url, 'headers' => $headers, 'body' => $body];
    [$status, $data] = $routes["$method $url"] ?? [404, ['error' => 'not_found']];

    return ['status' => $status, 'body' => is_string($data) ? $data : json_encode($data)];
};

$state = State::create('github');
check('State::create() makes distinct url-safe secrets', strlen($state->value) === 43 && $state->value !== $state->verifier && ! preg_match('/[+\/=]/', $state->value . $state->verifier . $state->nonce));
check('a state survives the session as an array', State::fromArray($state->toArray()) == $state);
check('a missing or malformed state is rejected', throws(fn () => State::fromArray(null), InvalidState::class) && throws(fn () => State::fromArray(['provider' => 'github']), InvalidState::class));
check('a state expires after TTL', new State('github', 'v', 'p', 'n', time() - State::TTL - 1)->isExpired() && ! $state->isExpired());

$auth = new Manager();
$auth->configure(
    providers: [
        'github' => ['client_id' => 'gh-id', 'client_secret' => 'gh-secret', 'redirect' => 'https://example.com/oauth/github/callback'],
        'google' => ['client_id' => 'g-id', 'client_secret' => 'g-secret', 'redirect' => 'https://example.com/oauth/google/callback'],
        'gitlab' => ['driver' => 'openid', 'issuer' => 'https://gitlab.com', 'client_id' => 'gl-id', 'client_secret' => 's', 'redirect' => 'https://example.com/cb'],
        'corp'   => ['driver' => 'sso'],
    ],
    transport: $transport,
);

check('getProviders() lists the configured names', $auth->getProviders() === ['github', 'google', 'gitlab', 'corp']);
check('drivers come from the name or the "driver" key', $auth->provider('github') instanceof GitHub && $auth->provider('google') instanceof OpenId && $auth->provider('gitlab') instanceof OpenId);
check('a provider is created once', $auth->provider('github') === $auth->provider('github'));
check('an unknown provider or driver is a configuration error', throws(fn () => $auth->provider('nope'), ProviderNotFound::class) && throws(fn () => $auth->provider('corp'), ProviderNotFound::class));

$auth->extend('sso', fn (array $config, string $name, ?Closure $transport) => new GitHub($name, 'id', 'secret', 'https://example.com/cb', transport: $transport));
check('extend() adds a driver', $auth->provider('corp') instanceof GitHub && $auth->provider('corp')->name === 'corp');

// GitHub
$github = $auth->provider('github');
parse_str(parse_url($url = $github->redirect($state), PHP_URL_QUERY), $query);
check('redirect() leads to the consent page with PKCE S256', str_starts_with($url, 'https://github.com/login/oauth/authorize?') && $query['code_challenge'] === b64(hash('sha256', $state->verifier, true)) && $query['code_challenge_method'] === 'S256');
check('redirect() passes the client, callback, scopes and state', $query['client_id'] === 'gh-id' && $query['redirect_uri'] === 'https://example.com/oauth/github/callback' && $query['scope'] === 'read:user user:email' && $query['state'] === $state->value);
check('redirect() rejects a state of another provider', throws(fn () => $github->redirect(State::create('google')), InvalidState::class));

$routes = [
    'POST https://github.com/login/oauth/access_token' => [200, ['access_token' => 'gho_token', 'token_type' => 'bearer']],
    'GET https://api.github.com/user'                  => [200, ['id' => 42, 'login' => 'octocat', 'name' => '', 'avatar_url' => 'https://avatars.example/42', 'email' => 'public@example.com']],
    'GET https://api.github.com/user/emails'           => [200, [
        ['email' => 'old@example.com', 'verified' => true, 'primary' => false],
        ['email' => 'main@example.com', 'verified' => true, 'primary' => true],
        ['email' => 'fake@example.com', 'verified' => false, 'primary' => false],
    ]],
];

$requests = [];
$profile  = $github->profile(['code' => 'abc', 'state' => $state->value], $state);
parse_str($requests[0]['body'], $exchange);
check('profile() exchanges the code with the verifier', $exchange['code'] === 'abc' && $exchange['code_verifier'] === $state->verifier && $exchange['client_secret'] === 'gh-secret' && $exchange['grant_type'] === 'authorization_code');
check('GitHub: the profile has the ID, login as the name and the avatar', $profile->provider === 'github' && $profile->id === '42' && $profile->name === 'octocat' && $profile->avatar === 'https://avatars.example/42');
check('GitHub: the email is the primary verified one, not the public one', $profile->email === 'main@example.com' && $profile->emailVerified);
check('GitHub: the API is called with the access token', $requests[1]['headers']['Authorization'] === 'Bearer gho_token' && $requests[1]['headers']['User-Agent'] === 'Expansa');

$routes['GET https://api.github.com/user/emails'] = [200, [['email' => 'fake@example.com', 'verified' => false, 'primary' => true]]];
$profile = $github->profile(['code' => 'abc', 'state' => $state->value], $state);
check('GitHub: without a verified email there is no email', $profile->email === null && ! $profile->emailVerified);

check('a foreign state is rejected', throws(fn () => $github->profile(['code' => 'abc', 'state' => 'forged'], $state), InvalidState::class));
check('a callback without a state is rejected', throws(fn () => $github->profile(['code' => 'abc'], $state), InvalidState::class));
check('a state of another provider is rejected', throws(fn () => $github->profile(['code' => 'abc', 'state' => $state->value], new State('google', $state->value, 'v', 'n', time())), InvalidState::class));
check('an expired state is rejected', throws(fn () => $github->profile(['code' => 'abc', 'state' => 'v'], new State('github', 'v', 'p', 'n', time() - State::TTL - 1)), InvalidState::class));
check('a callback without a code is rejected', throws(fn () => $github->profile(['state' => $state->value], $state), InvalidState::class));
check('a refused consent is Denied', throws(fn () => $github->profile(['error' => 'access_denied', 'state' => $state->value], $state), Denied::class));

$routes['POST https://github.com/login/oauth/access_token'] = [200, ['error' => 'bad_verification_code']];
check('a code the provider rejects fails the request', throws(fn () => $github->profile(['code' => 'abc', 'state' => $state->value], $state), RequestFailed::class));

$routes['POST https://github.com/login/oauth/access_token'] = [502, 'Bad gateway'];
check('an HTTP error fails the request', throws(fn () => $github->profile(['code' => 'abc', 'state' => $state->value], $state), RequestFailed::class));

$routes['POST https://github.com/login/oauth/access_token'] = [200, 'not json'];
check('a non-JSON answer fails the request', throws(fn () => $github->profile(['code' => 'abc', 'state' => $state->value], $state), RequestFailed::class));

// OpenID Connect
$rsa = file_get_contents(__DIR__ . '/fixtures/auth/rs256.pem');
$ec  = file_get_contents(__DIR__ . '/fixtures/auth/es256.pem');

$google  = $auth->provider('google');
$state   = State::create('google');
$claims  = fn (array $changes = []) => array_merge([
    'iss'            => 'https://accounts.google.com',
    'aud'            => 'g-id',
    'sub'            => '1100223344',
    'exp'            => time() + 3600,
    'iat'            => time(),
    'nonce'          => $state->nonce,
    'email'          => 'user@gmail.com',
    'email_verified' => true,
    'name'           => 'Jane Doe',
    'picture'        => 'https://lh3.example/photo.jpg',
], $changes);
$idToken = fn (array $changes = [], string $pem = '', array $header = ['alg' => 'RS256', 'kid' => 'rsa-1']) => jwt($claims($changes), $pem ?: $rsa, $header);

$routes = [
    'GET https://accounts.google.com/.well-known/openid-configuration' => [200, [
        'issuer'                 => 'https://accounts.google.com',
        'authorization_endpoint' => 'https://accounts.google.com/o/oauth2/v2/auth',
        'token_endpoint'         => 'https://oauth2.googleapis.com/token',
        'jwks_uri'               => 'https://www.googleapis.com/oauth2/v3/certs',
        'userinfo_endpoint'      => 'https://openidconnect.googleapis.com/v1/userinfo',
    ]],
    'GET https://www.googleapis.com/oauth2/v3/certs' => [200, ['keys' => [jwk($rsa, 'rsa-1'), jwk($ec, 'ec-1')]]],
];
$callback = function (string $token) use (&$routes, $google, $state) {
    $routes['POST https://oauth2.googleapis.com/token'] = [200, ['access_token' => 'ya29', 'id_token' => $token]];

    return $google->profile(['code' => 'xyz', 'state' => $state->value], $state);
};

parse_str(parse_url($url = $google->redirect($state), PHP_URL_QUERY), $query);
check('OpenID: redirect() uses the discovered endpoint and sends the nonce', str_starts_with($url, 'https://accounts.google.com/o/oauth2/v2/auth?') && $query['nonce'] === $state->nonce && $query['scope'] === 'openid email profile');

$profile = $callback($idToken());
check('OpenID: an RS256 identity token gives the profile', $profile->provider === 'google' && $profile->id === '1100223344' && $profile->email === 'user@gmail.com' && $profile->emailVerified && $profile->name === 'Jane Doe' && $profile->avatar === 'https://lh3.example/photo.jpg');
check('OpenID: an ES256 identity token gives the profile', $callback($idToken([], $ec, ['alg' => 'ES256', 'kid' => 'ec-1']))->id === '1100223344');
check('OpenID: a token without a key ID is matched by the key type', $callback($idToken([], $ec, ['alg' => 'ES256']))->id === '1100223344');
check('OpenID: the bare host issuer of Google is accepted', $callback($idToken(['iss' => 'accounts.google.com']))->id === '1100223344');
check('OpenID: an audience list with this client as azp is accepted', $callback($idToken(['aud' => ['other', 'g-id'], 'azp' => 'g-id']))->id === '1100223344');
check('OpenID: an unverified email is not trusted', ! $callback($idToken(['email_verified' => false]))->emailVerified);

$requests = [];
$routes['GET https://openidconnect.googleapis.com/v1/userinfo'] = [200, ['sub' => '1100223344', 'email' => 'info@gmail.com', 'email_verified' => true]];
$profile = $callback($idToken(['email' => null, 'email_verified' => null]));
check('OpenID: a missing email comes from userinfo with the access token', $profile->email === 'info@gmail.com' && end($requests)['headers']['Authorization'] === 'Bearer ya29');

$routes['GET https://openidconnect.googleapis.com/v1/userinfo'] = [200, ['sub' => 'someone-else', 'email' => 'evil@gmail.com']];
check('OpenID: userinfo of another subject is ignored', $callback($idToken(['email' => null]))->email === null);

foreach ([
    'another nonce'            => $idToken(['nonce' => 'replayed']),
    'another audience'         => $idToken(['aud' => 'other-client']),
    'an audience without azp'  => $idToken(['aud' => ['other', 'g-id']]),
    'another issuer'           => $idToken(['iss' => 'https://evil.example']),
    'an expired token'         => $idToken(['exp' => time() - 120]),
    'a token without subject'  => $idToken(['sub' => '']),
    'an unknown key'           => $idToken([], '', ['alg' => 'RS256', 'kid' => 'rotated']),
    'a key of another type'    => $idToken([], '', ['alg' => 'ES256', 'kid' => 'rsa-1']),
    'a forged signature'       => substr($idToken(), 0, -4) . 'AAAA',
    'the "none" algorithm'     => b64(json_encode(['alg' => 'none'])) . '.' . b64(json_encode($claims())) . '.',
    'an HMAC algorithm'        => $idToken([], '', ['alg' => 'HS256', 'kid' => 'rsa-1']),
    'a malformed token'        => 'not.a-token',
] as $case => $token) {
    check("OpenID: $case is rejected", throws(fn () => $callback($token), InvalidToken::class));
}

$routes['POST https://oauth2.googleapis.com/token'] = [200, ['access_token' => 'ya29']];
check('OpenID: a token response without an identity token is rejected', throws(fn () => $google->profile(['code' => 'xyz', 'state' => $state->value], $state), InvalidToken::class));

$gitlab = $auth->provider('gitlab');
$routes['GET https://gitlab.com/.well-known/openid-configuration'] = [200, ['issuer' => 'https://evil.example', 'authorization_endpoint' => 'https://evil.example/auth']];
check('OpenID: discovery of another issuer is rejected', throws(fn () => $gitlab->redirect(State::create('gitlab')), RequestFailed::class));

exit($failures > 0 ? 1 : 0);
