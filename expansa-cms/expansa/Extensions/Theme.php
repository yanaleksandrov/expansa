<?php

declare(strict_types=1);

namespace Expansa\Extensions;

use Expansa\Extensions\Internal\AbstractExtension;
use Expansa\Extensions\Contracts\Extension;
use InvalidArgumentException;

/**
 * Base of a theme: `themes/<slug>/index.php` returns an anonymous subclass. Besides the metadata
 * the theme declares its menu locations, the application assigns a menu to each of them:
 *
 * ```php
 * $this->setName('Aurora')->setMenus(['primary' => t('Header'), 'footer' => t('Footer')]);
 * ```
 *
 * @package Expansa\Extensions
 */
abstract class Theme extends AbstractExtension implements Extension
{
    public string $type = 'theme';

    /**
     * Menu locations of the theme: labels by key, a key of lowercase letters, digits, `-` and `_`.
     *
     * @var array<string, string>
     */
    public protected(set) array $menus = [];

    /**
     * Sets the menu locations of the theme, replacing the declared ones.
     *
     * @param array<array-key, string> $menus Labels by key.
     * @return static
     * @throws InvalidArgumentException When a key is not a valid location key.
     */
    protected function setMenus(array $menus): static
    {
        $locations = [];
        foreach ($menus as $key => $label) {
            if (! is_string($key) || ! preg_match('/^[a-z0-9_-]+$/', $key)) {
                throw new InvalidArgumentException(
                    "The menu location \"$key\" must consist of lowercase letters, digits, - and _."
                );
            }

            $locations[$key] = $this->sanitize($label);
        }
        $this->menus = $locations;

        return $this;
    }
}
