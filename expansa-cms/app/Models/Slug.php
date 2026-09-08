<?php

declare(strict_types=1);

namespace App\Models;

use Expansa\Database\Model;
use Expansa\Debug\Error;

/**
 * Represents a URL slug bound to a row in another table (the "entity"). Enforces
 * uniqueness the same way {@see User::nicename()} does for user nicenames: a
 * numeric suffix is appended whenever the requested slug is already taken.
 *
 * @property int    $id          Unique identifier of the slug record.
 * @property int    $entityId    ID of the entity this slug points to.
 * @property string $entityTable Name of the table the entity lives in.
 * @property string $slug        The URL-safe slug itself (unique).
 *
 * @package App\Models
 */
class Slug extends Model
{
    use Model\HasSanitizing;
    use Model\HasValidation;

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
     * Array of rules for sanitize properties.
     *
     * @return array<string, string>
     */
    protected function getSanitizerRules(): array
    {
        return [
            'entity_table' => 'trim',
            'slug'         => 'slug',
        ];
    }

    /**
     * An array of rules for validation when creating and updating a model.
     *
     * @return array<string, string>
     */
    protected function validatorRules(): array
    {
        return [
            'entity_id'    => 'required|numeric',
            'entity_table' => 'required',
            'slug'         => 'required|slug',
        ];
    }

    /**
     * Extend with custom validation rules.
     *
     * @return void
     */
    protected function validatorExtend(): void
    {
    }

    /**
     * Derives a unique, URL-safe slug from whatever value is set, appending a
     * numeric suffix if it would otherwise collide.
     */
    protected function slug(): Model\Attribute
    {
        return Model\Attribute::make(
            set: fn($value) => $this->generateUniqueSlug($value)
        );
    }

    /**
     * Retrieves a slug record by a given field.
     *
     * @param int|string $value A value for $by field. A slug ID, the slug string itself, or an entity table name.
     * @param string     $by    The field to retrieve the record with. id | slug | entity_table.
     * @return Slug|Error
     */
    public static function find(int|string $value, string $by = 'slug'): Slug|Error
    {
        if (empty($value)) {
            return error('slug-find', t('You are trying to find a slug with an empty :getByField.', $by));
        }

        if (! in_array($by, ['id', 'slug', 'entity_table'], true)) {
            return error('slug-find', t('Use an ID, slug, or entity table to get a slug.'));
        }

        $slug = parent::get($value, $by);

        return $slug instanceof Slug ? $slug : error('slug-find', t('Slug not found.'));
    }

    /**
     * Retrieves the slug record bound to a specific entity row.
     *
     * @param int    $entityId    ID of the entity the slug points to.
     * @param string $entityTable Name of the table the entity lives in.
     * @return Slug|Error
     */
    public static function findByEntity(int $entityId, string $entityTable): Slug|Error
    {
        $slug = static::where(['entity_id' => $entityId, 'entity_table' => $entityTable])->first();

        return $slug instanceof Slug ? $slug : error('slug-find', t('Slug not found.'));
    }

    /**
     * Create a new slug record for the given entity.
     *
     * @param array $data
     * @return Slug|Error
     */
    public static function create(array $data): Slug|Error
    {
        $slug = new self()->fill($data);

        if (! $slug->isValid()) {
            return error('slug-add', $slug->getValidatorErrors());
        }

        if (! $slug->save() instanceof self) {
            return error('slug-add', t('Failed to save the slug to the database.'));
        }

        return $slug;
    }

    /**
     * Update this slug record in the database with the given attributes.
     *
     * @param array $data
     * @return Slug|Error
     */
    public function update(array $data): Slug|Error
    {
        $this->fill($data);

        if (! $this->isValid()) {
            return error('slug-update', $this->getValidatorErrors());
        }

        if (! $this->save() instanceof self) {
            return error('slug-update', t('Failed to save the slug to the database.'));
        }

        return $this;
    }

    /**
     * Generate a unique slug by appending a numeric suffix if needed.
     *
     * This method checks the database for existing entries with the same slug
     * and increments the suffix until a unique value is found.
     *
     * @param string $value The base value to generate a unique slug from.
     * @return string A unique slug with a numeric suffix if necessary.
     */
    private function generateUniqueSlug(string $value): string
    {
        $suffix = 1;

        while ($this->exists(['slug' => $value . ( $suffix > 1 ? "-$suffix" : '' )])) {
            $suffix++;
        }

        return sprintf('%s%s', $value, $suffix > 1 ? "-$suffix" : '');
    }
}
