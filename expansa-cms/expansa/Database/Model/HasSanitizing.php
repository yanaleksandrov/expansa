<?php

declare(strict_types=1);

namespace Expansa\Database\Model;

trait HasSanitizing
{
    /**
     * Per-class cache backing {@see self::sanitizerRules()}.
     *
     * @var array<class-string, array<string, string>>
     */
    private static array $sanitizerRulesCache = [];

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
     * Cached wrapper around {@see self::getSanitizerRules()} - the rule set is a fixed array
     * literal per model class, but the abstract method itself allocates a fresh array on every
     * call; {@see \Expansa\Database\Model::setAttribute()} calls this once per attribute during
     * fill(), so an uncached call would rebuild the whole rule set once per attribute assigned.
     *
     * @return array<string, string>
     */
    protected function sanitizerRules(): array
    {
        return self::$sanitizerRulesCache[static::class] ??= $this->getSanitizerRules();
    }

    /**
     * Re-applies the sanitization rules to this model's current attribute values (e.g. after
     * attributes were set some other way than fill()/__set(), which already sanitize per-key).
     *
     * Merges the re-sanitized subset back over the full attribute set rather than replacing it
     * outright: {@see \Expansa\Security\Sanitizer::apply()} only returns the keys that actually
     * have a rule, and this model may well have others (id, timestamps, ...) that a wholesale
     * overwrite would silently drop. Deliberately bypasses mutators the same way make() does -
     * a rule here (e.g. 'trim') runs against whatever the attribute currently holds, which for a
     * mutated attribute (e.g. an already-hashed password) is its final, mutated form; routing
     * that back through the mutator would risk transforming it a second time.
     *
     * @return static
     */
    final protected function sanitize(): static
    {
        $sanitized = new \Expansa\Security\Sanitizer($this->getAttributes(), $this->sanitizerRules())->apply();

        $this->setAttributes($sanitized + $this->getAttributes());

        return $this;
    }
}
