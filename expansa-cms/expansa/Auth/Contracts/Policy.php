<?php

declare(strict_types=1);

namespace Expansa\Auth\Contracts;

/**
 * Rules of one resource type: which abilities a subject has on a given resource.
 * An unknown ability should be denied, so a new one is closed until a rule is written.
 *
 * @package Expansa\Auth
 */
interface Policy
{
    /**
     * Check if the subject may perform an ability on the resource.
     *
     * @param object|null $subject  Who acts, null for a guest.
     * @param string      $ability  Action name, e.g. "update".
     * @param object      $resource Instance of the class the policy is registered for.
     * @return bool
     */
    public function can(?object $subject, string $ability, object $resource): bool;
}
