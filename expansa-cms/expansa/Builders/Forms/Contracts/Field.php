<?php

declare(strict_types=1);

namespace Expansa\Builders\Forms\Contracts;

/**
 * Describes a single form field type (text, select, uploader, ...) that can be
 * registered with the form builder via {@see \Expansa\Facades\Form::configure()}.
 */
interface Field
{
    /**
     * Register the CSS & JS assets required to render this field type.
     */
    public function assets(): void;

    /**
     * Render the field's HTML markup.
     *
     * @param array $field Field configuration, as produced by a fields array
     *                      (name, label, attributes, options, ...).
     */
    public function render(array $field = []): string;

    /**
     * Field definitions for the settings panel shown when configuring an
     * instance of this field type in the field builder UI.
     *
     * @return array<int, array<string, mixed>>
     */
    public function settings(): array;

    /**
     * Server-side validation rules for this field type, in the format
     * expected by {@see \Expansa\Security\Validator}.
     *
     * @param array $field Field configuration the rules are derived from.
     * @return array<string, string>
     */
    public function validate(array $field = []): array;
}
