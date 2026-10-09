<?php

declare(strict_types=1);

namespace App\Api\User;

use App\Http\Can;
use Expansa\Http\Request;
use Expansa\Http\Response;

final readonly class UserController
{
    public function __construct(

        /**
         * Endpoint business logic; the default lets Kernel::dispatch() create the controller without arguments.
         */
        private UserService $service = new UserService(),
    ) {}

    public function update(Request $request, Response $response): Response
    {
        return $this->service->update($request, $response);
    }

    public function signIn(Request $request, Response $response): Response
    {
        return $this->service->signIn($request, $response);
    }

    public function emailLink(Request $request, Response $response): Response
    {
        return $this->service->emailLink($request, $response);
    }

    public function passkeyOptions(): array
    {
        return $this->service->passkeyOptions();
    }

    public function passkeySignIn(Request $request, Response $response): Response
    {
        return $this->service->passkeySignIn($request, $response);
    }

    public function passkeyCreateOptions(Request $request, Response $response): array|Response
    {
        return $this->service->passkeyCreateOptions($request, $response);
    }

    public function passkeyCreate(Request $request, Response $response): Response
    {
        return $this->service->passkeyCreate($request, $response);
    }

    public function passkeyDelete(Request $request, Response $response): Response
    {
        return $this->service->passkeyDelete($request, $response);
    }

    public function identityConnect(Request $request, Response $response): Response
    {
        return $this->service->identityConnect($request, $response);
    }

    public function identityDelete(Request $request, Response $response): Response
    {
        return $this->service->identityDelete($request, $response);
    }

    public function switchAccount(Request $request, Response $response): Response
    {
        return $this->service->switchAccount($request, $response);
    }

    public function sessionDelete(Request $request, Response $response): Response
    {
        return $this->service->sessionDelete($request, $response);
    }

    public function sessionsDeleteOthers(Request $request, Response $response): Response
    {
        return $this->service->sessionsDeleteOthers($request, $response);
    }

    public function twoFactor(Request $request, Response $response): Response
    {
        return $this->service->twoFactor($request, $response);
    }

    public function twoFactorSetup(Request $request, Response $response): Response
    {
        return $this->service->twoFactorSetup($request, $response);
    }

    public function twoFactorEnable(Request $request, Response $response): Response
    {
        return $this->service->twoFactorEnable($request, $response);
    }

    public function twoFactorDisable(Request $request, Response $response): Response
    {
        return $this->service->twoFactorDisable($request, $response);
    }

    public function twoFactorCodes(Request $request, Response $response): Response
    {
        return $this->service->twoFactorCodes($request, $response);
    }

    public function tokenCreate(Request $request, Response $response): Response
    {
        return $this->service->tokenCreate($request, $response);
    }

    public function tokenDelete(Request $request, Response $response): Response
    {
        return $this->service->tokenDelete($request, $response);
    }

    #[Can('users_edit')]
    public function adminStatus(Request $request, Response $response): Response
    {
        return $this->service->adminStatus($request, $response);
    }

    #[Can('users_edit')]
    public function adminSignOut(Request $request, Response $response): Response
    {
        return $this->service->adminSignOut($request, $response);
    }

    #[Can('users_edit')]
    public function adminPasswordReset(Request $request, Response $response): Response
    {
        return $this->service->adminPasswordReset($request, $response);
    }

    #[Can('users_edit')]
    public function adminTwoFactorDisable(Request $request, Response $response): Response
    {
        return $this->service->adminTwoFactorDisable($request, $response);
    }

    #[Can('users_edit')]
    public function impersonate(Request $request, Response $response): Response
    {
        return $this->service->impersonate($request, $response);
    }

    public function stopImpersonating(Response $response): Response
    {
        return $this->service->stopImpersonating($response);
    }

    public function confirm(Request $request, Response $response): Response
    {
        return $this->service->confirm($request, $response);
    }

    public function confirmPasskeyOptions(): array
    {
        return $this->service->confirmPasskeyOptions();
    }

    public function confirmPasskey(Request $request, Response $response): Response
    {
        return $this->service->confirmPasskey($request, $response);
    }

    public function signUp(Request $request, Response $response): Response
    {
        return $this->service->signUp($request, $response);
    }

    public function resetPassword(Request $request, Response $response): Response
    {
        return $this->service->resetPassword($request, $response);
    }

    public function passwordUpdate(Request $request, Response $response): Response
    {
        return $this->service->passwordUpdate($request, $response);
    }
}
