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

    public function update(Request $request): Response
    {
        return $this->service->update($request->post);
    }

    public function signIn(Request $request): Response
    {
        return $this->service->signIn($request->post);
    }

    public function emailLink(Request $request): Response
    {
        return $this->service->emailLink($request->post);
    }

    public function passkeyOptions(): array
    {
        return $this->service->passkeyOptions();
    }

    public function passkeySignIn(Request $request): Response
    {
        return $this->service->passkeySignIn($request->post);
    }

    public function passkeyCreateOptions(Request $request): array|Response
    {
        return $this->service->passkeyCreateOptions($request->post);
    }

    public function passkeyCreate(Request $request): Response
    {
        return $this->service->passkeyCreate($request->post);
    }

    public function passkeyDelete(Request $request): Response
    {
        return $this->service->passkeyDelete($request->post);
    }

    public function identityConnect(Request $request): Response
    {
        return $this->service->identityConnect($request->post);
    }

    public function identityDelete(Request $request): Response
    {
        return $this->service->identityDelete($request->post);
    }

    public function switchAccount(Request $request): Response
    {
        return $this->service->switchAccount($request->post);
    }

    public function sessionDelete(Request $request): Response
    {
        return $this->service->sessionDelete($request->post);
    }

    public function sessionsDeleteOthers(Request $request): Response
    {
        return $this->service->sessionsDeleteOthers($request->post);
    }

    public function twoFactor(Request $request): Response
    {
        return $this->service->twoFactor($request->post);
    }

    public function twoFactorSetup(Request $request): Response
    {
        return $this->service->twoFactorSetup($request->post);
    }

    public function twoFactorEnable(Request $request): Response
    {
        return $this->service->twoFactorEnable($request->post);
    }

    public function twoFactorDisable(Request $request): Response
    {
        return $this->service->twoFactorDisable($request->post);
    }

    public function twoFactorCodes(Request $request): Response
    {
        return $this->service->twoFactorCodes($request->post);
    }

    public function tokenCreate(Request $request): Response
    {
        return $this->service->tokenCreate($request->post);
    }

    public function tokenDelete(Request $request): Response
    {
        return $this->service->tokenDelete($request->post);
    }

    #[Can('users_edit')]
    public function adminStatus(Request $request): Response
    {
        return $this->service->adminStatus($request->post);
    }

    #[Can('users_edit')]
    public function adminSignOut(Request $request): Response
    {
        return $this->service->adminSignOut($request->post);
    }

    #[Can('users_edit')]
    public function adminPasswordReset(Request $request): Response
    {
        return $this->service->adminPasswordReset($request->post);
    }

    #[Can('users_edit')]
    public function adminTwoFactorDisable(Request $request): Response
    {
        return $this->service->adminTwoFactorDisable($request->post);
    }

    #[Can('users_edit')]
    public function impersonate(Request $request): Response
    {
        return $this->service->impersonate($request->post);
    }

    public function stopImpersonating(): Response
    {
        return $this->service->stopImpersonating();
    }

    public function confirm(Request $request): Response
    {
        return $this->service->confirm($request->post);
    }

    public function confirmPasskeyOptions(): array
    {
        return $this->service->confirmPasskeyOptions();
    }

    public function confirmPasskey(Request $request): Response
    {
        return $this->service->confirmPasskey($request->post);
    }

    public function signUp(Request $request): Response
    {
        return $this->service->signUp($request->input);
    }

    public function resetPassword(Request $request): Response
    {
        return $this->service->resetPassword($request->input);
    }

    public function passwordUpdate(Request $request): Response
    {
        return $this->service->passwordUpdate($request->post);
    }
}
