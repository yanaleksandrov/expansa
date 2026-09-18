<?php

declare(strict_types=1);

namespace Expansa\Builders\Forms\Fields;

use Expansa\Builders\Forms\Field;
use Expansa\Support\Arr;

/**
 * A repeatable group of sub-fields, keyed under `rows` (each row is itself a
 * `fields` array). There is no dedicated template or add/remove-row JS yet, so
 * this renders every existing row statically by delegating to {@see Field::parse()}.
 */
class Repeater extends AbstractField
{
    public function __construct()
    {
        parent::__construct(
            type: 'repeater',
            label: t('Repeater'),
            category: 'advanced',
            icon: 'ph ph-rows',
            description: t('A repeatable group of sub-fields.'),
            defaults: [
                'name'  => '',
                'label' => '',
                'rows'  => [],
            ],
        );
    }

    public function assets(): void
    {
        // No dedicated template/assets to discover: rows are rendered through the
        // generic Field parser, whose own field types register their own assets.
    }

    public function render(array $field = []): string
    {
        $field = [...$this->defaults, ...$field];
        $rows  = $field['rows'] ?: [[]];

        $content = '';
        foreach ($rows as $row) {
            $content .= new Field()->parse($row['fields'] ?? []);
        }

        return sprintf(
            "<div%s>\n%s</div>\n",
            Arr::toHtmlAtts(['class' => 'repeater', 'data-name' => $field['name']]),
            $content
        );
    }

    public function settings(): array
    {
        return $this->baseSettings(false);
    }

    public function validate(array $field = []): array
    {
        return [($field['name'] ?? '') => $this->withRequired($field, 'array')];
    }
}
