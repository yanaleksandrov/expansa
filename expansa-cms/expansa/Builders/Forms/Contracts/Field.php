<?php

declare(strict_types=1);

namespace Expansa\Builders\Forms\Contracts;

interface Field
{
    /**
     * Include required CSS & JS assets.
     */
    public function assets();

    /**
     * Path to render field interface.
     */
    public function render();

    /**
     * Create extra options for your field.
     * This is rendered when editing a field.
     */
    public function settings();

    /**
     * Server-side validation rules.
     */
    public function validate();
}
