<?php

declare(strict_types=1);

namespace App\Models;

use Expansa\Database\Model;
use Expansa\Debug\Error;

/**
 * Represents the usage of a {@see Term} as an actual taxonomy entry: how many times
 * it's attached to content, and, if any, the parent term it's nested under.
 *
 * Previously this class carried WordPress-style `register()`/`unregister()`/`isHierarchical(string $taxonomy)`
 * stubs modeled after {@see \App\Post\Type::register()} — an in-memory taxonomy-type registry. That doesn't
 * fit the `taxonomies` migration, which stores per-term rows (term_id, count, parent), not taxonomy-type
 * definitions, and neither stub had any implementation or caller. Converted to a plain CRUD row model
 * instead, matching {@see User}'s shape.
 *
 * @property int  $id     Unique identifier of the taxonomy record.
 * @property int  $termId ID of the {@see Term} this record attaches count/hierarchy to.
 * @property int  $count  Number of content items using this term.
 * @property int  $parent ID of the parent taxonomy record (0 if top-level).
 *
 * @package App\Models
 */
class Taxonomy extends Model
{
    use Model\HasValidation;

    /**
     * The database table associated with the model.
     *
     * @var string
     */
    protected string $table = 'taxonomies';

    /**
     * Fields allowed for mass assignment.
     *
     * @var array<string>
     */
    protected array $fillable = [
        'term_id',
        'count',
        'parent',
    ];

    /**
     * An array of rules for validation when creating and updating a model.
     *
     * @return array<string, string>
     */
    protected function validatorRules(): array
    {
        return [
            'term_id' => 'required|numeric',
        ];
    }

    /**
     * Extend with custom validation rules.
     *
     * @return void
     */
    protected function validatorExtend(): void {}

    /**
     * Retrieves a taxonomy record by a given field.
     *
     * @param int|string $value A value for $by field. A taxonomy ID or term ID.
     * @param string     $by    The field to retrieve the record with. id | term_id.
     * @return Taxonomy|Error
     */
    public static function find(int|string $value, string $by = 'id'): Taxonomy|Error
    {
        if (empty($value)) {
            return error('taxonomy-find', t('You are trying to find a taxonomy with an empty :getByField.', $by));
        }

        $by = mb_strtolower($by);
        if (! in_array($by, ['id', 'term_id'], true)) {
            return error('taxonomy-find', t('Use an ID or term ID to get a taxonomy.'));
        }

        $taxonomy = parent::get($value, $by);

        return $taxonomy instanceof Taxonomy ? $taxonomy : error('taxonomy-find', t('Taxonomy not found.'));
    }

    /**
     * Create a new taxonomy record in the database.
     *
     * @param array $data
     * @return Taxonomy|Error
     */
    public static function create(array $data): Taxonomy|Error
    {
        $data += ['count' => 0, 'parent' => 0];

        $taxonomy = new self()->fill($data);

        if (! $taxonomy->isValid()) {
            return error('taxonomy-add', $taxonomy->getValidatorErrors());
        }

        if (! $taxonomy->save() instanceof self) {
            return error('taxonomy-add', t('Failed to save the taxonomy to the database.'));
        }

        return $taxonomy;
    }

    /**
     * Update this taxonomy record in the database with the given attributes.
     *
     * @param array $data
     * @return Taxonomy|Error
     */
    public function update(array $data): Taxonomy|Error
    {
        $this->fill($data);

        if (! $this->isValid()) {
            return error('taxonomy-update', $this->getValidatorErrors());
        }

        if (! $this->save() instanceof self) {
            return error('taxonomy-update', t('Failed to save the taxonomy to the database.'));
        }

        return $this;
    }

    /**
     * The term this taxonomy record attaches count/hierarchy to.
     *
     * @return Term|Error
     */
    public function term(): Term|Error
    {
        return Term::find($this->term_id);
    }

    /**
     * Whether this taxonomy record is nested under a parent term.
     *
     * @return bool
     */
    public function isHierarchical(): bool
    {
        return $this->parent > 0;
    }
}
