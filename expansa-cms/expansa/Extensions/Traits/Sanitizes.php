<?php

declare(strict_types=1);

namespace Expansa\Extensions\Traits;

/**
 * Cleaning of metadata values set by an extension.
 *
 * @package Expansa\Extensions\Traits
 */
trait Sanitizes
{
    /**
     * Strip tags and escape HTML.
     *
     * @param string $value
     * @return string
     */
    public function sanitize(string $value): string
    {
        return htmlspecialchars(strip_tags($value));
    }

    /**
     * Remove the characters not allowed in a URL.
     *
     * @param string $url
     * @return string
     */
    public function sanitizeUrl(string $url): string
    {
        return strval(filter_var(trim($url), FILTER_SANITIZE_URL));
    }
}
