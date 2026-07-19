<?php

declare(strict_types=1);

namespace App\Models;

use Expansa\Database\Model;
use Expansa\Facades\Db;
use Expansa\Facades\Safe;

class Slug extends Model
{
    /**
     * The database table associated with the model.
     *
     * @var string
     */
    protected string $table = 'slugs';

    /**
     * Fields allowed for mass assignment.
     *
     * @var array<string>
     */
    protected array $fillable = [
        'entity_id',
        'entity_table',
        'slug',
    ];

    /**
     * Add new slug.
     *
     * @param int    $entityId
     * @param string $entityTable
     * @param string $slug
     * @return string|bool
     */
    public static function add(int $entityId, string $entityTable, string $slug): string|bool
    {
        $slug = Safe::slug($slug);

        try {
            return Db::insert(
                self::$table,
                [
                    'entity_id'    => $entityId,
                    'entity_table' => $entityTable,
                    'slug'         => $slug,
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
    public static function find(int $entityId, string $entityTable): string
    {
        return Db::get(self::$table, 'slug', ['entity_id' => $entityId, 'entity_table' => $entityTable]) ?? '';
    }

    /**
     * Get data by slug.
     *
     * @param string $slug
     * @return mixed
     */
    public static function get(string $slug): mixed
    {
        return Db::get((new self())->getTable(), '*', ['slug' => $slug]);
    }

    /**
     * Update slug.
     *
     * @param string $slug
     * @param string $newSlug
     * @return bool
     */
    public static function update(string $slug, string $newSlug): bool
    {
        return Db::update((new self())->getTable(), ['slug' => $newSlug], ['slug[=]' => $slug])->rowCount() === 1;
    }

    /**
     * Delete slug.
     *
     * @param string $value
     * @param string $by
     * @return bool
     */
    public static function delete(string $value, string $by = 'slug'): bool
    {
        if (! in_array($by, ['entity_id', 'entity_table', 'slug'], true)) {
            return false;
        }
        return Db::delete(self::$table, [$by => $value])->rowCount() > 0;
    }
}
