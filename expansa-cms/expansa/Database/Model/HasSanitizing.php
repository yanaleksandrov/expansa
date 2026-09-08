<?php

declare(strict_types=1);

namespace Expansa\Database\Model;

use Expansa\Security\Sanitizer;

trait HasSanitizing
{
    /**
     * Returns the sanitization rules for this model.
     *
     * Example:
     * [
     *     'email'     => 'trim|email',
     *     'firstname' => 'trim|ucfirst',
     * ]
     *
     * @return array<string, string>  Associative array of attribute => rule.
     */
    abstract protected function getSanitizerRules(): array;

    /**
     * Apply the sanitization rules to the model's attributes.
     *
     * @return static Returns the model instance after sanitization.
     */
    final protected function sanitize(): static
    {
        echo '<pre>';
        var_dump(23124235);
        print_r($this->getAttributes());
        print_r($this->getSanitizerRules());
        echo '</pre>';
        $data = new Sanitizer($this->getAttributes(), $this->getSanitizerRules())->apply();

        $this->setAttributes($data);

        return $this;
    }
}
