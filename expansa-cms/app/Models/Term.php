<?php

declare(strict_types=1);

namespace App\Models;

use Expansa\Database\Model;
use Expansa\Debug\Error;

/**
 * Represents a taxonomy term: a reusable name/slug pair (e.g. a category or tag)
 * that {@see Taxonomy} attaches usage count and hierarchy to.
 *
 * @property int    $id        Unique identifier of the term.
 * @property string $name      Display name of the term.
 * @property string $slug      URL-safe, unique identifier derived from the name.
 * @property int    $termGroup Arbitrary grouping ID for terms meant to be treated as synonyms.
 * @property Field  $field     A dynamic meta field instance associated with the term.
 *
 * @package App\Models
 */
class Term extends Model
{
    use Model\HasSanitizing;
    use Model\HasValidation;
    use Model\HasFieldEav;

    /**
     * One-to-many with Field: the foreign key column on "terms_fields" (the "many" side)
     * that points back to this term (see Model\HasFieldEav).
     *
     * @var string
     */
    public string $fieldsForeignKey = 'term_id';

    /**
     * The database table associated with the model.
     *
     * @var string
     */
    protected string $table = 'terms';

    /**
     * Fields allowed for mass assignment.
     *
     * @var array<string>
     */
    protected array $fillable = [
        'name',
        'slug',
        'term_group',
    ];

    /**
     * Array of rules for sanitize properties.
     *
     * @return array<string, string>
     */
    protected function getSanitizerRules(): array
    {
        return [
            'name'       => 'trim',
            'slug'       => 'slug:$name',
            'term_group' => 'absint',
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
            'name' => 'required',
            'slug' => 'required|slug',
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
     * Derives a unique, URL-safe slug from whatever value is set,
     * appending a numeric suffix if it would otherwise collide.
     */
    protected function slug(): Model\Attribute
    {
        return Model\Attribute::make(
            set: fn($value) => $this->generateUniqueSlug($value)
        );
    }

    /**
     * Retrieves a term by a given field.
     *
     * @param int|string $value A value for $by field. A term ID or slug.
     * @param string     $by    The field to retrieve the term with. id | slug.
     * @return Term|Error
     */
    public static function find(int|string $value, string $by = 'id'): Term|Error
    {
        if (empty($value)) {
            return error('term-find', t('You are trying to find a term with an empty :getByField.', $by));
        }

        $by = mb_strtolower($by);
        if (! in_array($by, ['id', 'slug'], true)) {
            return error('term-find', t('Use an ID or slug to get a term.'));
        }

        $term = parent::get($value, $by);

        return $term instanceof Term ? $term : error('term-find', t('Term not found.'));
    }

    /**
     * Create a new term in the database.
     *
     * @param array $data
     * @return Term|Error
     */
    public static function create(array $data): Term|Error
    {
        // Only takes effect if the key is entirely absent — fill() sanitizes/mutates only keys $data actually has.
        $data += ['slug' => ''];

        $term = new self()->fill($data);

        if (! $term->isValid()) {
            return error('term-add', $term->getValidatorErrors());
        }

        if (! $term->save() instanceof self) {
            return error('term-add', t('Failed to save the term to the database.'));
        }

        return $term;
    }

    /**
     * Update this term in the database with the given attributes.
     *
     * @param array $data
     * @return Term|Error
     */
    public function update(array $data): Term|Error
    {
        $this->fill($data);

        if (! $this->isValid()) {
            return error('term-update', $this->getValidatorErrors());
        }

        if (! $this->save() instanceof self) {
            return error('term-update', t('Failed to save the term to the database.'));
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
