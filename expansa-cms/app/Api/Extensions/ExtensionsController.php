<?php

declare(strict_types=1);

namespace App\Api\Extensions;

/**
 * Note: nothing in the dashboard currently calls this endpoint — the real extensions
 * list (dashboard/views/table/header.blade.php) renders from the actual framework
 * facade Expansa\Facades\Extensions, not this class. This is UI-prototype mock data,
 * kept as-is per the migration (not deleted, since that wasn't asked for here).
 */
final readonly class ExtensionsController
{
    public function __construct(private ExtensionsService $service = new ExtensionsService())
    {
    }

    public function get(): array
    {
        return $this->service->list();
    }
}
