<?php

declare(strict_types=1);

namespace App\Api\Apikey;

use App\Http\Can;
use Expansa\Http\Request;
use Expansa\Http\Response;

final readonly class ApikeyController
{
    public function __construct(

        /**
         * Endpoint business logic; the default lets Kernel::dispatch() create the controller without arguments.
         */
        private ApikeyService $service = new ApikeyService(),
    ) {}

    /**
 * @todo not implemented — placeholder carried over from the legacy class
*/
    #[Can('manage_options')]
    public function index(): array
    {
        return ['method' => 'PUT update user by ID'];
    }

    #[Can('manage_options')]
    public function create(Request $request): Response|array
    {
        return $this->service->create($request->post);
    }

    /**
 * @todo not implemented — placeholder carried over from the legacy class
*/
    #[Can('manage_options')]
    public function update(): array
    {
        return ['method' => 'PUT update user by ID'];
    }

    /**
     * @todo not implemented — the legacy class never had real logic here either, only
     *       the placeholder. The dashboard (user-profile.php) does call this expecting
     *       a real delete, so this is a known gap, not something invented here.
     */
    #[Can('manage_options')]
    public function delete(): array
    {
        return ['method' => 'DELETE remove user by ID'];
    }
}
