<?php

declare(strict_types=1);

namespace Expansa\Builders\Table;

use Expansa\Facades\Safe;

class Row
{
    /**
     * Add new row.
     *
     * @param string $tag Tag for row wrapper.
     * @param string $view Path to get view for render table row.
     * @param array $attributes Attributes list.
     */
    public function __construct(
        public string $tag = 'div',
        public string $view = 'table/row',
        public array $attributes = []
    ) {} // phpcs:ignore

    /**
     * Set row attribute.
     *
     * @param string $attribute
     * @param string|int $value
     * @return Row
     */
    public function attribute(string $attribute, string|int $value = ''): Row
    {
        $attribute = Safe::name($attribute);
        $value     = Safe::attribute($value);
        if ($attribute && $value) {
            $this->attributes[ $attribute ] = $value;
        }
        return $this;
    }

    /**
     * Get view template.
     *
     * @param string $template
     * @return Row
     */
    public function view(string $template): Row
    {
        if (file_exists($template)) {
            $this->view = $template;
        } else {
            $this->view = sprintf('%s/%s', $this->view, $template);
        }

        return $this;
    }
}
