<?php

declare(strict_types=1);

namespace App\Dashboard\Pages;

use App\Models\Term as Entry;
use App\Models\User;
use App\Post\Taxonomy;
use Expansa\Access\Exceptions\AccessDenied;
use Expansa\Http\Exceptions\NotFound;
use Expansa\Http\Request;

/**
 * Editor of a term: its name, slug, parent and description; the list of the terms has the form of a new one.
 *
 * @package App\Dashboard
 */
final class Term
{
    /**
     * Terms read in the request by ID: the page data and the `term-editor` form ask for the same one.
     *
     * @var array<int, Entry>
     */
    private static array $terms = [];

    /**
     * Data of the `term` page: the term of `id` and its taxonomy; `type` is the post type the list
     * of the terms was opened from.
     *
     * @param Request $request
     * @return array{term: Entry, taxonomy: Taxonomy, type: string}
     * @throws NotFound When there is no such term or its taxonomy is not registered.
     * @throws AccessDenied When the viewer may not change the terms of the taxonomy.
     */
    public static function data(Request $request): array
    {
        $term     = self::term($request->getInt('id'), '');
        $taxonomy = Taxonomy::get($term->taxonomy) ?? throw new NotFound(t('Term not found'));

        if (! $taxonomy->canManage(User::current())) {
            throw new AccessDenied(implode(', ', $taxonomy->capabilities));
        }

        $type = $request->getString('type');

        return [
            'term'     => $term,
            'taxonomy' => $taxonomy,
            'type'     => in_array($type, $taxonomy->types, true) ? $type : ($taxonomy->types[0] ?? ''),
        ];
    }

    /**
     * Get a term by ID, a new unsaved term of the taxonomy for the ID 0.
     *
     * @param int    $id
     * @param string $taxonomy Key of the taxonomy of a new term.
     * @return Entry
     * @throws NotFound When there is no such term or taxonomy.
     */
    public static function term(int $id, string $taxonomy): Entry
    {
        if (isset(self::$terms[$id])) {
            return self::$terms[$id];
        }

        $term = match (true) {
            $id > 0                           => Entry::find($id),
            Taxonomy::get($taxonomy) !== null => Entry::hydrate([
                'id'          => 0,
                'taxonomy'    => $taxonomy,
                'name'        => '',
                'slug'        => '',
                'description' => '',
                'parent_id'   => 0,
            ]),
            default                           => null,
        };
        if (! $term instanceof Entry) {
            throw new NotFound(t('Term not found'));
        }

        return self::$terms[$id] = $term;
    }

    /**
     * Get the terms of the same taxonomy a term can be nested in, as a tree by position and name, a dash per level;
     * the term and its descendants are left out, nesting in them makes a loop.
     *
     * @param Entry $term
     * @return array<int, string> Names by ID; `0` for no parent first.
     */
    public static function parents(Entry $term): array
    {
        $parents = [0 => t('None')];
        $skipped = -1;
        foreach (Entry::tree($term->taxonomy) as $row) {
            // the children of the term come right after it, deeper than it
            if ($skipped >= 0 && $row['depth'] > $skipped) {
                continue;
            }

            $skipped = $row['id'] === $term->id ? $row['depth'] : -1;
            if ($skipped < 0) {
                $parents[$row['id']] = str_repeat('— ', $row['depth']) . $row['name'];
            }
        }

        return $parents;
    }
}
