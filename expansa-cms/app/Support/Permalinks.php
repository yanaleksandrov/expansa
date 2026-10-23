<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Option;
use Expansa\Http\Exceptions\ValidationFailed;

/**
 * Structures of the URLs from the SEO settings: a post is `permalinks.pages.single` with `%slug%`
 * (the path under the parents), `%id%` and `%type%`; a term is `permalinks.pages.categories` with
 * `%slug%` and `%taxonomy%`. A structure is a path relative to the site address.
 */
final class Permalinks
{
    /**
     * Structures used while the settings have none.
     */
    public const array DEFAULTS = [
        'single'     => '%slug%',
        'categories' => '%taxonomy%/%slug%',
    ];

    /**
     * Tags each structure may have.
     */
    private const array TAGS = [
        'single'     => ['%slug%', '%id%', '%type%'],
        'categories' => ['%slug%', '%taxonomy%'],
    ];

    /**
     * Get the URL path of a post.
     *
     * @param string $path Path of the post, see Post::paths(): `parent/child`.
     * @param int    $id
     * @param string $type Key of the post type.
     * @return string Empty for a post without a path.
     */
    public static function post(string $path, int $id, string $type): string
    {
        return $path === '' ? '' : self::fill('single', [
            '%slug%' => $path,
            '%id%'   => (string) $id,
            '%type%' => $type,
        ]);
    }

    /**
     * Get the URL of a post around its slug, for the slug field: up to the slug with the paths
     * of the parents, and the rest after it.
     *
     * @param string $parents Path of the parent, empty without one.
     * @param int    $id
     * @param string $type    Key of the post type.
     * @return array{string, string}
     */
    public static function around(string $parents, int $id, string $type): array
    {
        $marker = "\x1A";

        [$before, $after] = explode($marker, self::post(ltrim("$parents/$marker", '/'), $id, $type), 2);

        return [url($before), $after];
    }

    /**
     * Get the URL path of a term.
     *
     * @param string $slug
     * @param string $taxonomy Key of the taxonomy.
     * @return string Empty for a term without a slug.
     */
    public static function term(string $slug, string $taxonomy): string
    {
        return $slug === '' ? '' : self::fill('categories', [
            '%slug%'     => $slug,
            '%taxonomy%' => $taxonomy,
        ]);
    }

    /**
     * Check the structures of the form: lower case, no slashes at the ends, only known tags,
     * `%slug%` in each and `%taxonomy%` in the one of the terms, so that two entries never get one URL.
     *
     * @param array<string, mixed> $pages Form values by structure.
     * @return array<string, string>
     * @throws ValidationFailed When a structure has no required tag, an unknown tag or other characters.
     */
    public static function normalize(array $pages): array
    {
        $result = [];
        foreach (self::DEFAULTS as $key => $default) {
            $structure = strtolower(trim((string) ($pages[$key] ?? ''), " \t/"));
            $structure = (string) preg_replace('#/+#', '/', $structure) ?: $default;

            preg_match_all('/%[a-z]+%/', $structure, $tags);

            $isValid = preg_match('#^[a-z0-9_%/-]+$#', $structure) === 1
                && str_contains($structure, '%slug%')
                && ($key !== 'categories' || str_contains($structure, '%taxonomy%'))
                && array_diff($tags[0], self::TAGS[$key]) === []
                && substr_count($structure, '%') === 2 * count($tags[0]);
            if (! $isValid) {
                $tags    = implode(', ', self::TAGS[$key]);
                $message = $key === 'categories'
                    ? t(
                        'Use Latin letters, digits, hyphens, slashes and the tags :tags; :slug and :taxonomy are required.',
                        $tags,
                        '%slug%',
                        '%taxonomy%',
                    )
                    : t('Use Latin letters, digits, hyphens, slashes and the tags :tags; :slug is required.', $tags, '%slug%');

                throw ValidationFailed::field("permalinks.pages.$key", $message);
            }

            $result[$key] = $structure;
        }

        return $result;
    }

    /**
     * Put the values into the tags of a structure.
     *
     * @param string                $key    `single` or `categories`.
     * @param array<string, string> $values Values by tag.
     * @return string
     */
    private static function fill(string $key, array $values): string
    {
        return strtr((string) Option::get("permalinks.pages.$key", '') ?: self::DEFAULTS[$key], $values);
    }
}
