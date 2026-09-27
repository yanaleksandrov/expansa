<?php

declare(strict_types=1);

namespace Expansa\Database\Traits;

use LogicException;

/**
 * Validation of the attributes by the validator from Model::configure(). The model declares the rules
 * in validatorRules() and adds custom ones to $this->validator in validatorExtend().
 *
 * @package Expansa\Database\Traits
 */
trait HasValidation
{
    /**
     * Validator of the current attributes, created by the first check: isValid() and
     * getValidatorErrors() validate once.
     */
    protected ?object $validator = null;

    /**
     * Get the rules by attribute.
     *
     * @return array<string, string>
     */
    abstract protected function validatorRules(): array;

    /**
     * Add custom rules to $this->validator.
     *
     * @return void
     */
    abstract protected function validatorExtend(): void;

    final public function isValid(): bool
    {
        return $this->validate()->isValid();
    }

    /**
     * @return array<string, array<int, string>>
     */
    final public function getValidatorErrors(): array
    {
        return $this->validate()->errors;
    }

    /**
     * Create the validator once, add the custom rules and validate.
     *
     * @param bool $break Stop on the first failed rule.
     * @return object
     * @throws LogicException If no validator is configured.
     */
    protected function validate(bool $break = false): object
    {
        if ($this->validator !== null) {
            return $this->validator;
        }

        if (self::$validatorFactory === null) {
            throw new LogicException('Validation rules of ' . static::class . ' need Model::configure(validator: ...).');
        }

        $this->validator = (self::$validatorFactory)($this->attributes, $this->validatorRules(), $break);

        $this->validatorExtend();

        $this->validator->apply();

        return $this->validator;
    }
}
