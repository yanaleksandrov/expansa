<?php

declare(strict_types=1);

namespace Expansa\Assets\Support;

use Expansa\Assets\Abstracts\Provider;

trait AssetHandler
{
    /**
     * Sort assets by dependencies.
     *
     * @param array $assets
     * @return array
     */
    public function sortDependencies(array $assets): array
    {
        $dependents = array_filter($assets, fn (Provider $asset) => ! empty($asset->dependencies));
        $assets     = array_filter($assets, fn (Provider $asset) => empty($asset->dependencies));

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

    /**
     * Convert absolute URL of file to path.
     *
     * @param string $url
     * @return string
     */
    public static function toPath(string $url): string
    {
        $rootPath = explode('/expansa/', __DIR__)[0] ?? '';
        $filepath = $rootPath . DIRECTORY_SEPARATOR . ltrim(parse_url($url, PHP_URL_PATH), '/');

        return is_file($filepath) ? $filepath : '';
    }

    /**
     * Convert array to attributes.
     *
     * @param array $attributes
     * @return string
     */
    public function sanitizeAttributes(array $attributes): string
    {
        $result = [];

        $attributes = array_merge(
            array_intersect_key($attributes, array_flip(['id', 'type', 'rel'])),
            array_diff_key($attributes, array_flip(['id', 'type', 'rel']))
        );

        foreach ($attributes as $attribute => $value) {
            if (! is_scalar($attribute) || ! is_scalar($value)) {
                continue;
            }

            $attribute = trim(htmlspecialchars((string) $attribute, ENT_QUOTES, 'UTF-8'));
            $value     = trim(htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'));
            if ($attribute) {
                if (in_array($attribute, ['async', 'defer'], true) && ! empty($value)) {
                    $result[] = $attribute;
                }

                if ($value) {
                    $result[] = sprintf('%s="%s"', $attribute, $value);
                }
            }
        }

        return $result ? ' ' . implode(' ', $result) : '';
    }

    /**
     * Sanitize ID of asset.
     *
     * @param string $uid
     * @return string
     */
    public function sanitizeId(string $uid): string
    {
        $uid = str_replace(['_', '.', ',', ' '], '-', $uid);

        return trim(preg_replace('/\W-/', '', $uid));
    }

    /**
     * Sanitize ID of asset for usage as JS const name.
     *
     * @param string $value
     * @return string
     */
    public function sanitizeConst(string $value): string
    {
        $value = trim($value);
        $value = str_replace(['_', '-'], ' ', $value);
        $value = ucwords($value);
        $value = str_replace(' ', '', $value);

        return lcfirst($value);
    }

    /**
     * Sanitize asset url.
     *
     * @param string $url
     * @return string
     */
    public function sanitizeUrl(string $url): string
    {
        // remove leading and trailing whitespace
        $url = trim($url);

        // normalize slashes
        $url = str_replace(';//', '://', $url);

        // remove characters other than letters, numbers, hyphens, underscores, dots, and slashes
        $url = preg_replace('/[^a-zA-Z0-9-_.:\/?]/', '', $url);

        // remove double and triple slashes but not :// after http protocol
        return preg_replace('#([^:])//+#', '\1/', $url);
    }
}
