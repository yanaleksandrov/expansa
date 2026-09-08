<?php

declare(strict_types=1);

namespace App\Api\User;

use App\Models\User;
use Expansa\Debug\Error;
use Expansa\Http\Request;

final readonly class UserController
{
    public function __construct(private UserService $service = new UserService())
    {
    }

    /** @todo not implemented — placeholder carried over from the legacy class */
    public function create(): array
    {
        return ['method' => 'POST create user'];
    }

    /** @todo not implemented — placeholder carried over from the legacy class */
    public function index(): array
    {
        return ['method' => 'GET user list'];
    }

    public function update(Request $request): array
    {
        return $this->service->update($request->post());
    }

    /** @todo not implemented — placeholder carried over from the legacy class */
    public function delete(): array
    {
        return ['method' => 'DELETE remove user by ID'];
    }

    public function signIn(Request $request): array
    {
        return $this->service->signIn($request->post());
    }

    public function signUp(Request $request): array|User
    {
        return $this->service->signUp($request->all());
    }

    public function resetPassword(Request $request): array|Error
    {
        return $this->service->resetPassword($request->all());
    }

    /**
     * @todo not implemented. The legacy method was a completely empty body despite being
     *       declared `: array` — under strict_types that's an immediate TypeError the
     *       moment it's called, and the dashboard (user-profile.php) does call it. There
     *       was no logic at all to migrate, so this is a stub instead of a crash — a real
     *       password-change flow (current-password check, hashing, etc.) still needs to
     *       be written, that's genuine new logic and out of scope for a migration.
     */
    public function passwordUpdate(): array
    {
        return ['method' => 'not implemented'];
    }
}
