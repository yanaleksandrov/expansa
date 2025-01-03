<?php

declare(strict_types=1);

namespace Expansa\Asset;

class Dependency
{
    /**
     * Sort assets by dependencies.
     *
     * @param array $assets
     * @return array
     */
    public static function sort(array $assets): array
    {
        $dependents = array_filter($assets, fn ($item) => ! empty($item['dependencies']));
        $assets     = array_filter($assets, fn ($item) => empty($item['dependencies']));

        foreach ($dependents as $key => $dependent) {
            $dependencies = $dependent['dependencies'] ?? [];
            if (empty($dependencies) || ! is_array($dependencies)) {
                continue;
            }

            $index    = null;
            $filtered = array_intersect_key($assets, array_flip($dependencies));
            if ($filtered) {
                $index = max(array_keys($filtered));
            }

            if ($index) {
                $splitIndex = array_search($index, array_keys($assets), true);
                $primary    = array_slice($assets, 0, $splitIndex + 1, true);
                $secondary  = array_slice($assets, $splitIndex + 1, null, true);
                $assets     = $primary + [$key => $dependent] + $secondary;
            }
        }

        return $assets;
    }
}
