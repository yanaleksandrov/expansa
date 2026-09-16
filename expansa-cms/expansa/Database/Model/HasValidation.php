<?php

declare(strict_types=1);

namespace Expansa\Database\Model;

use Expansa\Security\Validator;

/**
 * Trait provides validation rules for create & update model. Classes using this trait must
 * implement `validatorRules()` and `validatorExtend()`; `validate()` itself is already provided.
 */
trait HasValidation
{
    /**
     * The last Validator built by {@see self::validate()} - cached for the model's lifetime, so
     * isValid() immediately followed by getValidatorErrors() (the common pattern) doesn't
     * validate twice.
     *
     * @var null|Validator
     */
    protected ?Validator $validator = null;

    /**
     * An array of rules for validation when creating and updating a model.
     *
     * @return array<string, string>
     */
    abstract protected function validatorRules(): array;

    /**
     * Extend with custom validation rules.
     *
     * @return void
     */
    abstract protected function validatorExtend(): void;

    /**
     * @return bool
     */
    final public function isValid(): bool
    {
        return $this->validate()->isValid();
    }

    /**
     * @return array<string, array<int, string>>
     */
    final public function getValidatorErrors(): array
    {
        return $this->validate()->getErrors();
    }

    /**
     * Initialize and execute validator for the current model state.
     *
     * Creates the validator instance only once, applies custom extensions,
     * executes validation, and returns the initialized validator.
     *
     * @param bool $break Stop validation on the first failed rule.
     * @return Validator Initialized validator instance.
     */
    protected function validate(bool $break = false): Validator
    {
        if ($this->validator !== null) {
            return $this->validator;
        }

        $this->validator = new Validator($this->getAttributes(), $this->validatorRules(), $break);

        $this->validatorExtend();

        $this->validator->apply();

        return $this->validator;
    }
}
