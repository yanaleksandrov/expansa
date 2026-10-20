<?php

declare(strict_types=1);

namespace Expansa\Builders\Form\Values;

use Expansa\Security\Sanitizer;

/**
 * Option of a field, built by Data from an item of `options` or `locations`: a label by its key, an array
 * with `content` and the keys below, or a group of a select, `label` and nested `options`.
 *
 * @package Expansa\Builders\Form
 */
final class Option
{
    /**
     * Reactive property of a checkbox named by the key, `u-prop`.
     */
    public string $prop {
        get => Sanitizer::prop($this->value);
    }

    public function __construct(

        /**
         * Key of the option in the field array, the value it submits.
         */
        public readonly string $value = '',

        /**
         * Label, may contain markup; of a group its `label`.
         */
        public readonly string $content = '',

        /**
         * Hint under the label, may contain markup.
         */
        public readonly string $description = '',

        /**
         * Classes of the icon.
         */
        public readonly string $icon = '',

        /**
         * Classes of a button option.
         */
        public readonly string $class = '',

        /**
         * Whether a checkbox or a radio is checked, null takes the `checked` attribute of the field.
         */
        public readonly ?bool $checked = null,

        /**
         * File size of an image quality example, KB.
         */
        public readonly int $size = 0,

        /**
         * HTML attributes of a button option.
         *
         * @var array<string, mixed>
         */
        public readonly array $attributes = [],

        /**
         * Keys of the option array but `content` and `options` as the `data-*` attributes of a select option.
         *
         * @var array<string, string>
         */
        public readonly array $data = [],

        /**
         * Options of a group.
         *
         * @var Option[]
         */
        public readonly array $options = [],
    ) {}
}
