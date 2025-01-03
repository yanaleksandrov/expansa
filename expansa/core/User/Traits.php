<?php

declare(strict_types=1);

namespace Expansa\User;

/**
 * @since 2025.1
 */
trait Traits
{
    /**
     * Session key
     *
     * @var string
     */
    private static string $session_id = EX_DB_PREFIX . 'user_logged';

    /**
     * Current user data.
     *
     * @since 2025.1
     */
    private static $current;
}
