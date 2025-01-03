<?php

declare(strict_types=1);

namespace Expansa\Hooks;

use ReflectionException;

abstract class HooksCollector
{
    use AttributesReader;

    protected static array $hooks = [];

    /**
     * Returns unique ID for a given hook name and function
     *
     * This is used as a unique identifier for remove()
     *
     * @param string $hookName
     * @param mixed  $function String with function name, anonymous function, array with class & method
     *
     * @return string
     * @throws ReflectionException
     */
    protected function makeId(string $hookName, mixed $function): string
    {
        return match (true) {
            is_string($function) => md5($hookName . $function),
            is_array($function)  => md5(spl_object_hash($function[0]) . $function[1]),
            default              => md5(spl_object_hash($function)),
        };

        $function = match (true) {
            is_array($function) && is_object($function[0]) => spl_object_hash($function[0]),
            is_array($function) && isset($function[0])     => spl_object_hash($function[0]),
            $function instanceof \Closure                  => $this->getAlias($function),
            default                                        => $function,
        };

        return md5(sprintf('%s::%s', $hookName, $function));
    }

    /**
     * Returns array of all hooks or for a specific hook type
     *
     * This method exists simply to reindex array keys when calling getFilters()
     *
     * @param $name (Name of hook to return, or NULL for all)
     *
     * @return array
     */
    protected function getHooks(?string $name = null): array
    {
        $hooks = self::$hooks;

        if (null === $name) {
            foreach ($hooks as $hook => $functions) {
                $hooks[$hook] = array_values($functions);
            }
            return $hooks;
        }

        if (isset($hooks[$name])) {
            return array_values($hooks[$name]);
        }

        return [];
    }

    /**
     * Sort a multidimensional array by a given key in ascending (optionally, descending) order.
     *
     * @param array  $array      Original array
     * @param string $key        Key name to sort by
     * @param bool   $descending Sort descending
     *
     * @return array
     */
    public static function multisort(array $array, string $key, bool $descending = false): array
    {
        $columns = array_column($array, $key);
        if (false === $descending) {
            array_multisort($columns, SORT_ASC, $array, SORT_NUMERIC);
        } else {
            array_multisort($columns, SORT_DESC, $array, SORT_NUMERIC);
        }
        return $array;
    }
}
