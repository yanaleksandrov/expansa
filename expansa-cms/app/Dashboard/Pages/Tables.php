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
     * Data of the `edit` page: the table of the list named by `table`, posts by default.
     *
     * @param Request $request
     * @return array{table: Table}
     */
    public static function data(Request $request): array
    {
        $table = match ($request->getString('table')) {
            'comments'    => new Comments(),
            'translation' => new Translations($request->getString('project')),
            'emails'      => new Emails(),
            'users'       => new Users(),
            'files'       => new Media(),
            default       => new Pages(),
        };

        return ['table' => $table];
    }
}
