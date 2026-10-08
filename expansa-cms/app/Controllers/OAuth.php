<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Api\User\Events;
use App\Api\User\Identities;
use App\Api\User\SignIn;
use App\Models\User;
use Expansa\Auth\Exceptions\Denied;
use Expansa\Auth\Exceptions\TooManyAttempts;
use Expansa\Auth\OAuth\State;
use Expansa\Facades\Auth;
use Expansa\Facades\Log;
use Expansa\Facades\Session;
use Expansa\Http\Redirect;
use Expansa\Support\Error;
use Throwable;

/**
 * Sign-in through a provider of the Security settings: `/oauth/<name>` leaves a guest for the provider,
 * `/oauth/<name>/callback` signs in, or connects the provider started from the profile with
 * the password (see UserService::identityConnect()). A failure returns with a general reason,
 * the details go to the log.
 */
final class OAuth
{
    /**
     * Start a sign-in of a guest; a signed-in user connects providers from the profile.
     *
     * @param string $provider
     * @return void
     */
    public function redirect(string $provider): void
    {
        if (Auth::isLoggedIn() && ! isset($_GET['add'])) {
            redirect('dashboard');
        }

        try {
            $url = Identities::start($provider, redirectTo: (string) ($_GET['redirect_to'] ?? ''));
        } catch (TooManyAttempts) {
            $this->fail('oauth-limited', 'sign-in');
        } catch (Throwable $e) {
            $this->fail('oauth-failed', 'sign-in', $e);
        }

        Redirect::send($url);
    }

    /**
     * Finish: the attempt is taken out of the session first, so a callback can't be replayed.
     *
     * @param string $provider
     * @return void
     */
    public function callback(string $provider): void
    {
        if (! Session::isStarted()) {
            Session::start();
        }

        $data = Session::get(Identities::SESSION_KEY);
        Session::forget(Identities::SESSION_KEY);

        $current = ($data['link'] ?? false) === true ? User::current() : null;
        $back    = $current !== null ? 'dashboard/profile' : 'sign-in';

        try {
            $profile = Auth::provider($provider)->profile($_GET, State::fromArray($data));
        } catch (Throwable $e) {
            $this->fail($e instanceof Denied ? 'oauth-denied' : 'oauth-failed', $back, $e);
        }

        $user = Identities::resolve($profile, $current);
        if ($user instanceof Error) {
            $this->fail($user->code, $back);
        }

        if ($current !== null) {
            Events::record($current, 'provider_connected', ['provider' => $provider]);
            redirect('dashboard/profile');
        }

        $refusal = SignIn::refusal($user);
        if ($refusal !== null) {
            $this->fail('oauth-refused', 'sign-in');
        }

        Redirect::send(SignIn::finish($user, false, $provider, (string) ($data['redirect_to'] ?? '')));
    }

    /**
     * Return with the reason in the query, logging what went wrong.
     *
     * @param string         $code  Reason the page shows: `oauth-denied`, `oauth-email`, ...
     * @param string         $to    Page to return to.
     * @param Throwable|null $error
     * @return never
     */
    private function fail(string $code, string $to, ?Throwable $error = null): never
    {
        if ($error !== null && ! $error instanceof Denied) {
            Log::warning('OAuth sign-in failed: {message}', ['message' => $error->getMessage(), 'exception' => $error]);
        }

        redirect("$to?error=$code");
        exit;
    }
}
