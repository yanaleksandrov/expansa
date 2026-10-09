<?php

declare(strict_types=1);

namespace App\Dashboard\Pages;

use App\Tables\Comments;
use App\Tables\Emails;
use App\Tables\Media;
use App\Tables\Pages;
use App\Tables\Translations;
use App\Tables\Users;
use Expansa\Builders\Table;
use Expansa\Http\Request;

/**
 * The `edit` page: a table of the post type or the list named by `table`, posts of a type by default.
 *
 * @package App\Dashboard
 */
final class Tables
{
    /**
     * Tables of the lists that are not post types, by `table`.
     *
     * @var array<string, class-string<Table>>
     */
    private const array TABLES = [
        'comments'    => Comments::class,
        'translation' => Translations::class,
        'emails'      => Emails::class,
        'users'       => Users::class,
        'files'       => Media::class,
    ];

    /**
     * Template of the `edit` page: files are shown by the media library.
     *
     * @param Request $request
     * @return string
     */
    public static function view(Request $request): string
    {
        return $request->getString('table') === 'files' ? 'screens/media' : 'screens/edit';
    }

    /**
     * Data of the `edit` page: the table.
     *
     * @param Request $request
     * @return array{table: Table}
     */
    public static function data(Request $request): array
    {
        return ['table' => new (self::TABLES[$request->getString('table')] ?? Pages::class)()];
    }
}
