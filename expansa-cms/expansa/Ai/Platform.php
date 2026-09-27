<?php

declare(strict_types=1);

namespace Expansa\Ai;

/**
 * PHP version and extensions available to generated code.
 * The model receives them in every prompt; `Validators\Extensions` checks the generated code against them.
 * Pass the target server's values when code is generated on another machine.
 */
final class Platform
{
    /**
     * Available extension names in lower case.
     *
     * @var string[]
     */
    public readonly array $extensions;

    /**
     * Stores the PHP version and normalizes extension names.
     */
    public function __construct(

        /**
         * PHP version generated code runs on.
         */
        public readonly string $version = PHP_VERSION,

        /**
         * Available extension names; null takes the extensions loaded in this process.
         *
         * @var string[]|null
         */
        ?array $extensions = null,
    ) {
        $this->extensions = array_values(array_unique(array_map(strtolower(...), $extensions ?? get_loaded_extensions())));
    }

    /**
     * Checks whether an extension is available.
     *
     * @param string $extension Extension name in any case
     */
    public function has(string $extension): bool
    {
        return in_array(strtolower($extension), $this->extensions, true);
    }
}
