<?php

declare(strict_types=1);

namespace Expansa\Extensions\Traits;

trait ExtensionHelpers
{
    public function sanitize(string $value): string
    {
        return htmlspecialchars(strip_tags($value));
    }

    public function sanitizeUrl(string $url): string
    {
        return strval(filter_var(trim($url), FILTER_SANITIZE_URL));
    }
}
