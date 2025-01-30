<?php

declare(strict_types=1);

namespace App;

use Expansa\Facades\Db;
use Expansa\Facades\Safe;

final class Slug
{
    /**
     * DB table name.
     *
     * @var string
     */
    private static string $table = 'slugs';

    /**
     * Add new slug.
     *
     * @param int    $entityId
     * @param string $entityTable
     * @param string $slug
     * @param string $locale
     * @return string|bool
     */
    public static function add(int $entityId, string $entityTable, string $slug, string $locale = ''): string|bool
    {
        $slug = Safe::slug($slug);

        try {
            return Db::insert(
                self::$table,
                [
                    'entity_id'    => $entityId,
                    'entity_table' => $entityTable,
                    'slug'         => $slug,
                    'locale'       => $locale,
                ]
            )->rowCount() > 0;
        } catch (\Exception $e) {
            $slugs = Db::select(self::$table, 'slug', [
                'AND' => [
                    'slug[REGEXP]' => sprintf('^%s(-[1-9][0-9]*)?$', preg_quote($slug, '/')),
                ],
            ]);

            // checking the uniqueness of a slug, add a numeric suffix if found
            $maxSuffix = 0;
            if (count($slugs) === 1 && $slugs[0] === $slug) {
                $maxSuffix = 1;
            } else {
                $slugs = array_diff($slugs, [ $slug ]);
                if ($slugs) {
                    $maxSuffix = max(array_map(fn($item) => (int) substr(strrchr($item, '-'), 1), $slugs));
                }
            }

            if ($maxSuffix > 0) {
                $slug = sprintf('%s-%d', $slug, $maxSuffix + 1);
            }
        }

        $isInsert = Db::insert(
            self::$table,
            [
                'entity_id'    => $entityId,
                'entity_table' => $entityTable,
                'slug'         => $slug,
                'locale'       => $locale,
            ]
        )->rowCount() > 0;

        return $isInsert ? $slug : '';
    }

    /**
     * Get entity slug.
     *
     * @param int $entityId
     * @param string $entityTable
     * @return string
     */
    public static function getByEntity(int $entityId, string $entityTable): string
    {
        return Db::get(self::$table, 'slug', [ 'entity_id' => $entityId, 'entity_table' => $entityTable ]) ?? '';
    }

    /**
     * Get data by slug.
     *
     * @param string $slug
     * @param string $locale
     * @return mixed
     */
    public static function get(string $slug, string $locale = ''): mixed
    {
        return Db::get(self::$table, '*', [ 'slug' => $slug, 'locale' => $locale ]);
    }

    /**
     * @param string $slug
     * @param string $newSlug
     * @param string $locale
     * @return bool
     */
    public static function update(string $slug, string $newSlug, string $locale = ''): bool
    {
        return Db::update(self::$table, [ 'slug' => $newSlug ], [ 'slug[=]' => $slug ])->rowCount() === 1;
    }

    /**
     * @param string $value
     * @param string $by
     * @return bool
     */
    public static function delete(string $value, string $by = 'slug'): bool
    {
        if (! in_array($by, [ 'uuid', 'entity_id', 'entity_table', 'slug', 'locale' ], true)) {
            return false;
        }
        return Db::delete(self::$table, [ $by => $value ])->rowCount() > 0;
    }
}
