<?php

declare(strict_types=1);

namespace Expansa\Builders\Form\Fields;

/**
 * Renders the `slug` template: the address of an entry as a line of its URL, the slug edited in place.
 * A new entry takes the slug from its source field while the title is typed, a saved one keeps it
 * until it is emptied; the slug is normalized as Sanitizer::slug() does.
 *
 * Keys: `from` is the name of the source field, `base` the URL before the slug, `suffix` the part after it;
 * with `parent`, the name of a field, and `bases`, the URLs by its values, the base follows that field.
 */
final class Slug extends AbstractField
{
    public function __construct()
    {
        parent::__construct(
            type: 'slug',
            label: t('Slug'),
            category: 'basic',
            icon: 'ph ph-link-simple',
            description: t('The address of an entry made from its title and edited in place.'),
            defaults: [
                'name'       => 'slug',
                'label'      => '',
                'from'       => 'title',
                'attributes' => ['type' => 'text'],
            ],
        );
    }

    public function settings(): array
    {
        return [
            ...$this->baseSettings(false),
            [
                'type'  => 'text',
                'name'  => 'from',
                'error' => 'from',
                'label' => t('Source field'),
            ],
        ];
    }

    public function validate(array $field = []): array
    {
        return [($field['name'] ?? '') => $this->withRequired($field, 'slug')];
    }
}
