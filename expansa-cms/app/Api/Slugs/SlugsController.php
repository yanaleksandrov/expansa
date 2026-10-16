<?php

declare(strict_types=1);

namespace App\Api\Slugs;

use App\Http\Can;
use Expansa\Http\Request;
use Expansa\Security\Sanitizer;

final readonly class SlugsController
{
    /**
     * Slug of a text for the `slug` field while it is typed: Sanitizer::slug() with the transliteration
     * of the Permalinks settings. Uniqueness is checked on saving.
     *
     * @param Request $request `text`, the source of the slug.
     * @return string
     */
    #[Can('read')]
    public function generate(Request $request): string
    {
        return Sanitizer::slug($request->getString('text'));
    }
}
