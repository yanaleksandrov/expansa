<?php

declare(strict_types=1);

namespace App\Api\User;

use App\Models\User;
use Expansa\Debug\Error;
use Expansa\Http\Request;

final readonly class UserController
{
    public function __construct(

        /**
         * Endpoint business logic; the default lets Kernel::dispatch() create the controller without arguments.
         */
        private UserService $service = new UserService(),
    ) {}

    /**
 * @todo not implemented — placeholder carried over from the legacy class
*/
    public function create(): array
    {
        return ['method' => 'POST create user'];
    }

    /**
 * @todo not implemented — placeholder carried over from the legacy class
*/
    public function index(): array
    {
        return ['method' => 'GET user list'];
    }

    public function update(Request $request): array
    {
        return $this->service->update($request->post);
    }

    /**
 * @todo not implemented — placeholder carried over from the legacy class
*/
    public function delete(): array
    {
        return ['method' => 'DELETE remove user by ID'];
    }

    public function signIn(Request $request): array
    {
        return $this->service->signIn($request->post);
    }

    public function passkeyOptions(): array
    {
        return $this->service->passkeyOptions();
    }

    public function passkeySignIn(Request $request): array
    {
        return $this->service->passkeySignIn($request->post);
    }

    public function passkeyCreateOptions(Request $request): array
    {
        return $this->service->passkeyCreateOptions($request->post);
    }

    public function passkeyCreate(Request $request): array
    {
        return $this->service->passkeyCreate($request->post);
    }

    public function passkeyDelete(Request $request): array
    {
        return $this->service->passkeyDelete($request->post);
    }

    public function signUp(Request $request): array|User
    {
        return $this->service->signUp($request->input);
    }

    public function resetPassword(Request $request): array
    {
        return $this->service->resetPassword($request->input);
    }

    public function passwordUpdate(Request $request): array
    {
        return $this->service->passwordUpdate($request->post);
    }
}
