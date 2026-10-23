<?php

declare(strict_types=1);

namespace App\Dashboard\Pages;

use App\Post\Taxonomy;
use App\Post\Type;
use App\Tables\Comments;
use App\Tables\Emails;
use App\Tables\Media;
use App\Tables\Posts;
use App\Tables\Terms;
use App\Tables\Translations;
use App\Tables\Users;
use Expansa\Builders\Table;
use Expansa\Http\Exceptions\NotFound;
use Expansa\Http\Request;

/**
 * The `edit` page: a table of the list or the post type named by `table`, pages by default;
 * `terms` lists the terms of the taxonomy of `taxonomy`.
 *
 * @package App\Dashboard
 */
final class Tables
{
    /**
     * Template of the `edit` page: files are shown by the media library, terms with the form of a new one.
     *
     * @param Request $request
     * @return string
     */
    public static function view(Request $request): string
    {
        return match ($request->getString('table')) {
            'files' => 'screens/media',
            'terms' => 'screens/terms',
            default => 'screens/edit',
        };
    }

    /**
     * Data of the `edit` page: the table of the list named by `table`, else of the post type, pages by default.
     *
     * @param Request $request
     * @return array{table: Table}
     * @throws NotFound When `table` is neither a list nor a post type, or `taxonomy` is not registered.
     */
    public static function data(Request $request): array
    {
        $table = match ($request->getString('table')) {
            'comments'    => new Comments(
                $request->getString('status'),
                $request->getString('view') === 'trash',
                $request->getString('s'),
            ),
            'translation' => new Translations($request->getString('project')),
            'emails'      => new Emails(),
            'users'       => new Users(
                $request->getString('status'),
                $request->getString('s'),
            ),
            'files'       => new Media(),
            'terms'       => new Terms(
                Taxonomy::get($request->getString('taxonomy')) ?? throw new NotFound(),
                $request->getString('type'),
                $request->getString('s'),
            ),
            default       => new Posts(
                Type::get($request->getString('table') ?: 'pages') ?? throw new NotFound(),
                $request->getString('view') === 'trash',
                $request->getString('status'),
                $request->getString('s'),
            ),
        };

        return ['table' => $table];
    }
}
