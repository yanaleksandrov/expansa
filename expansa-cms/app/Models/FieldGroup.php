<?php

declare(strict_types=1);

namespace App\Models;

use Expansa\Database\Model;
use Expansa\Database\Model\HasSanitizing;
use Expansa\Database\Model\HasTimestamps;
use Expansa\Database\Model\HasValidation;
use Expansa\Debug\Error;
use Expansa\Facades\Json;

/**
 * A group of custom fields (ACF-style "Field Group"): a title, a set of location rules
 * describing where it should appear, and the field definitions themselves. `location` and
 * `fields` are stored as JSON - `fields` uses the exact same array shape already consumed by
 * {@see \Expansa\Builders\Forms\Field::parse()}, so a saved group can be rendered as-is.
 *
 * @property int    $id
 * @property string $title
 * @property string $slug
 * @property array  $location
 * @property array  $fields
 * @property int    $position
 * @property string $status
 * @property string $createdAt
 * @property string $updatedAt
 */
class FieldGroup extends Model
{
    use HasSanitizing;
    use HasTimestamps;
    use HasValidation;

    protected string $table = 'field_groups';

    /**
     * Fields allowed for mass assignment.
     *
     * @var array<string>
     */
    protected array $fillable = [
        'title',
        'slug',
        'position',
        'status',
    ];

    protected function getSanitizerRules(): array
    {
        return [
            'title'    => 'trim',
            'slug'     => 'slug:$title',
            'position' => 'absint',
            'status'   => 'id:active',
        ];
    }

    protected function validatorRules(): array
    {
        return [
            'title' => 'required',
            'slug'  => 'required|slug',
        ];
    }

    protected function validatorExtend(): void
    {
    }

    /**
     * Derives a unique, URL-safe slug from whatever value is set, appending a numeric
     * suffix if it would otherwise collide (the column has a unique constraint).
     *
     * Same approach as {@see Term::generateUniqueSlug()}: `exists()` can't exclude this
     * row's own ID, so re-saving an existing group without changing its title will bump
     * the suffix again rather than keeping the slug stable - an accepted limitation here,
     * not something specific to this model.
     */
    protected function slug(): Model\Attribute
    {
        return Model\Attribute::make(
            set: function ($value) {
                $suffix = 1;
                while ($this->exists(['slug' => $value . ($suffix > 1 ? "-$suffix" : '')])) {
                    $suffix++;
                }

                return sprintf('%s%s', $value, $suffix > 1 ? "-$suffix" : '');
            }
        );
    }

    /**
     * Location rules built by the `builder` field type - an array of rule groups, or an
     * empty array to mean "no location rules yet" (the group won't appear anywhere until set).
     */
    protected function location(): Model\Attribute
    {
        return Model\Attribute::make(
            get: fn ($value) => $value ? Json::decode($value, true) : [],
            set: fn ($value) => Json::encode(is_array($value) ? $value : []),
        );
    }

    /**
     * Field definitions, in the same shape {@see \Expansa\Builders\Forms\Field::parse()} expects.
     */
    protected function fields(): Model\Attribute
    {
        return Model\Attribute::make(
            get: fn ($value) => $value ? Json::decode($value, true) : [],
            set: fn ($value) => Json::encode(is_array($value) ? $value : []),
        );
    }

    /**
     * Retrieve a field group by ID or slug.
     *
     * @param int|string $value
     * @param string     $by    id | slug
     * @return FieldGroup|Error
     */
    public static function find(int|string $value, string $by = 'id'): FieldGroup|Error
    {
        if (empty($value)) {
            return error('field-group-find', t('You are trying to find a field group with an empty :getByField.', $by));
        }

        $by = mb_strtolower($by);
        if (! in_array($by, ['id', 'slug'], true)) {
            return error('field-group-find', t('Use an ID or slug to get a field group.'));
        }

        $group = parent::get($value, $by);

        return $group instanceof FieldGroup ? $group : error('field-group-find', t('Field group not found.'));
    }

    /**
     * Create a new field group.
     *
     * @param array $data
     * @return FieldGroup|Error
     */
    public static function create(array $data): FieldGroup|Error
    {
        $data += ['slug' => ''];

        $group = new self($data);
        $group->location = $data['location'] ?? [];
        $group->fields   = $data['fields'] ?? [];

        if (! $group->isValid()) {
            return error('field-group-create', $group->getValidatorErrors());
        }

        if (! $group->save() instanceof self) {
            return error('field-group-create', t('Failed to save the field group to the database.'));
        }

        return $group;
    }

    /**
     * Update this field group with the given attributes.
     *
     * @param array $data
     * @return FieldGroup|Error
     */
    public function update(array $data): FieldGroup|Error
    {
        $this->fill($data);

        if (array_key_exists('location', $data)) {
            $this->location = $data['location'];
        }

        if (array_key_exists('fields', $data)) {
            $this->fields = $data['fields'];
        }

        if (! $this->isValid()) {
            return error('field-group-update', $this->getValidatorErrors());
        }

        if (! $this->save() instanceof self) {
            return error('field-group-update', t('Failed to save the field group to the database.'));
        }

        return $this;
    }
}
