<?php

declare(strict_types=1);

namespace Expansa\Auth\Exceptions;

use RuntimeException;

/**
 * Thrown by Manager::authorize() when the subject lacks the permission or the ability.
 * A decision, not a failure: the caller turns it into a 403 response, a CLI error or a redirect.
 *
 * @package Expansa\Auth
 */
final class AccessDenied extends RuntimeException
{
    public function __construct(

        /**
         * Denied permission, or ability when there is a resource.
         */
        public readonly string $ability,

        /**
         * Resource of the denied ability, null for a permission.
         */
        public readonly ?object $resource = null,
    ) {
        parent::__construct($resource === null
            ? "Permission [$ability] is denied."
            : "Ability [$ability] on [" . $resource::class . '] is denied.');
    }
}
