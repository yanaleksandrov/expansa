<?php

declare(strict_types=1);

namespace Expansa\Builders\Forms\Fields;

/**
 * Renders `form/password.blade.php` - a password input with an optional visibility
 * switcher, strength indicator and generator.
 */
class Password extends AbstractField
{
    public function __construct()
    {
        parent::__construct(
            type: 'password',
            label: t('Password'),
            category: 'basic',
            icon: 'ph ph-lock-key',
            description: t('A password input with an optional visibility switcher, strength indicator and generator.'),
            defaults: [
                'name'       => '',
                'label'      => '',
                'switcher'   => true,
                'indicator'  => false,
                'generator'  => false,
                'attributes' => [],
            ],
        );
    }

    public function settings(): array
    {
        return [
            ...$this->baseSettings(),
            $this->toggle('switcher', t('Show visibility switcher')),
            $this->toggle('indicator', t('Show strength indicator')),
            $this->toggle('generator', t('Show password generator')),
        ];
    }

    /**
     * A single checkbox settings row, e.g. "Show visibility switcher".
     */
    private function toggle(string $name, string $label): array
    {
        return ['type' => 'checkbox', 'name' => $name, 'label' => '', 'options' => [$name => ['content' => $label]]];
    }

    public function validate(array $field = []): array
    {
        $minLength = $field['characters']['length'] ?? null;
        $rule      = $minLength ? 'lengthMin:' . (int) $minLength : '';

        return [($field['name'] ?? '') => $this->withRequired($field, $rule)];
    }
}
