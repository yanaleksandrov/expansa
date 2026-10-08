<?php

declare(strict_types=1);

namespace App\Support;

use App\Api\User\Tokens;
use Expansa\Access\Contracts\Permissions;

/**
 * Permissions of roles narrowed to the scopes of the API token of the request, see Tokens:
 * a token never grants more than its owner has, nor more than it was created for.
 */
final readonly class TokenPermissions implements Permissions
{
    public function __construct(

        /**
         * Permissions of the roles.
         */
        private Permissions $roles,
    ) {}

    public function has(?object $subject, string $permission): bool
    {
        return $this->roles->has($subject, $permission) && Tokens::allows($permission);
    }
}
