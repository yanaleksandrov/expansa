<?php

declare(strict_types=1);

namespace Expansa\Builders\Form\Fields;

use Expansa\Builders\Form\Internal\Renderer;
use Expansa\Support\Arr;

/**
 * A repeatable group of sub-fields, keyed under `rows` (each row is itself a
 * `fields` array). There is no dedicated template or add/remove-row JS yet, so
 * this renders every existing row statically by delegating to {@see Renderer::render()}.
 */
final class Repeater extends AbstractField
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
        // rows are rendered by Renderer::render(), which connects the assets of each field
    }

    public function render(array $field = []): string
    {
        $field = [...$this->defaults, ...$field];
        $rows  = $field['rows'] ?: [[]];

        $content = '';
        foreach ($rows as $row) {
            $content .= new Renderer()->render($row['fields'] ?? []);
        }

        $attributes = [
            'class'     => 'repeater',
            'data-name' => $field['name'],
        ];

        return sprintf("<div%s>\n%s</div>\n", Arr::toHtmlAttributes($attributes), $content);
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
