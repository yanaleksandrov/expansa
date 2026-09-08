<?php

declare(strict_types=1);

namespace App\Api\Apikey;

use Expansa\Http\Request;
use Expansa\Http\Response;

final readonly class ApikeyController
{
    public function __construct(private ApikeyService $service = new ApikeyService())
    {
    }

    /** @todo not implemented — placeholder carried over from the legacy class */
    public function index(): array
    {
        return ['method' => 'PUT update user by ID'];
    }

    public function create(Request $request): Response|array
    {
        return $this->service->create($request->post());
    }

    /** @todo not implemented — placeholder carried over from the legacy class */
    public function update(): array
    {
        return ['method' => 'PUT update user by ID'];
    }

    /**
     * @todo not implemented — the legacy class never had real logic here either, only
     *       the placeholder. The dashboard (user-profile.php) does call this expecting
     *       a real delete, so this is a known gap, not something invented here.
     */
    public function delete(): array
    {
        return ['method' => 'DELETE remove user by ID'];
    }
}
