<?php

declare(strict_types=1);

namespace Expansa\Builders\Form\Fields;

/**
 * Renders the `slug` template: the address of an entry as a line of its URL, the slug edited in place.
 * A new entry takes the slug from its source field while the title is typed, a saved one keeps it
 * until one click takes it from the title again; the slug is normalized as Sanitizer::slug() does.
 *
 * Options: `from` is the name of the source field, `base` the URL before the slug,
 * `link` the address of the saved entry for the Open button.
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
                'options'    => ['from' => 'title'],
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
                'name'  => 'options.from',
                'error' => 'options.from',
                'label' => t('Source field'),
            ],
        ];
    }

    public function validate(array $field = []): array
    {
        return [($field['name'] ?? '') => $this->withRequired($field, 'slug')];
    }
}
