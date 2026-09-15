<?php

declare(strict_types=1);

namespace Expansa\Assets\Support;

use Expansa\Assets\Abstracts\Provider;

trait AssetHandler
{
    /**
     * Dirs {@see self::cacheMinifiedFile()} already confirmed exist this request.
     */
    protected static array $ensuredCacheDirs = [];

    /**
     * Above this many bytes, {@see self::readContent()} skips caching in APCu. APCu is one
     * fixed-size shared segment for every small hot item on the box (not just ours); a large
     * blob eats a disproportionate share of it, fragments the segment (making even smaller
     * future allocations fail), and can silently evict other callers' entries to make room.
     */
    protected const int APCU_MAX_BYTES = 1024 * 1024;

    /**
     * Sort assets so every asset comes after the assets it depends on (topological sort).
     * A dependency may reference either an asset's `uid` or its full composite id
     * (`{uid}-{extension}`); unknown dependencies are ignored.
     *
     * @param array<string, Provider> $assets
     * @return array<string, Provider>
     */
    public function sortDependencies(array $assets): array
    {
        $idsByUid = [];
        foreach ($assets as $id => $asset) {
            $idsByUid[$asset->uid][] = $id;
        }

        $sorted  = [];
        $visited = [];

        $visit = function (string $id) use (&$visit, &$sorted, &$visited, $assets, $idsByUid): void {
            if (isset($visited[$id]) || !isset($assets[$id])) {
                return;
            }
            $visited[$id] = true;

            foreach ($assets[$id]->dependencies as $dependency) {
                foreach ($idsByUid[$dependency] ?? [$dependency] as $dependencyId) {
                    $visit($dependencyId);
                }
            }

            $sorted[$id] = $assets[$id];
        };

        foreach (array_keys($assets) as $id) {
            $visit($id);
        }

        return $sorted;
    }

    /**
     * Resolve the absolute filesystem path behind a public asset URL.
     * Returns an empty string when the URL does not map to an existing file
     * (e.g. it points to an external CDN).
     */
    public static function toPath(string $url): string
    {
        $file = rtrim(EX_PATH, '/\\') . DIRECTORY_SEPARATOR . ltrim((string) parse_url($url, PHP_URL_PATH), '/');

        return is_file($file) ? $file : '';
    }

    /**
     * Convert an absolute filesystem path back into a public, site-relative URL.
     * Inverse of toPath().
     */
    public static function toUrl(string $path): string
    {
        $path = str_replace('\\', '/', $path);
        $root = str_replace('\\', '/', rtrim(EX_PATH, '/\\')) . '/';

        $relative = str_starts_with($path, $root) ? substr($path, strlen($root)) : ltrim($path, '/');

        return url($relative);
    }

    /**
     * Read $asset's local file, running it through its own minify() when $minify is true. Null
     * when there's no local file to read (an external URL). No is_file() check: $asset->path is
     * only ever non-empty because toPath() already confirmed it moments earlier; the
     * file_get_contents() failure check below covers the rare case of it disappearing since.
     *
     * When $minify is true and APCu is loaded, the result is also cached in APCu keyed by
     * path+mtime - unlike everything else here, that survives past this request (a fresh
     * PHP-FPM worker starts with a clean slate). A file's mtime in the key means a change is a
     * cache miss by construction. Without APCu this block is skipped entirely: plain read+minify.
     * Content over {@see self::APCU_MAX_BYTES} is still minified normally but never stored -
     * APCu is for lots of small hot items, not the odd multi-megabyte bundle.
     *
     * Caveat for persistent runtimes only (Swoole, RoadRunner - not classic PHP-FPM): PHP caches
     * filemtime() for the process lifetime, so a file changed after its first read may keep
     * resolving to its old mtime/cache entry until clearstatcache() runs. Not calling that here
     * on every read, since it would reintroduce the stat this is meant to avoid.
     */
    public function readContent(Provider $asset, bool $minify): ?string
    {
        if ($asset->path === '') {
            return null;
        }

        if (! $minify) {
            $content = @file_get_contents($asset->path);
            return $content === false ? null : $content;
        }

        $cacheKey = null;
        if (function_exists('apcu_fetch')) {
            $mtime = @filemtime($asset->path);

            if ($mtime !== false) {
                $cacheKey = 'expansa:asset:minify:1:' . $asset::class . ':' . $asset->path . ':' . $mtime;

                $cached = apcu_fetch($cacheKey, $success);
                if ($success) {
                    return $cached;
                }
            }
        }

        $content = @file_get_contents($asset->path);
        if ($content === false) {
            return null;
        }

        $minified = $asset->minify($content);

        if ($cacheKey !== null && strlen($minified) <= self::APCU_MAX_BYTES) {
            apcu_store($cacheKey, $minified, 86400);
        }

        return $minified;
    }

    /**
     * Write $content to a content-addressed file under `cache/assets/$extension` (created on
     * first use) and return its public URL. Filename = $name + a short content hash, so a
     * content change gives a new URL on its own - no `?ver=` cache-busting needed.
     *
     * Null on failure (disk full, permissions, ...) rather than a URL to a file that was never
     * actually written - callers fall back to the original, uncached file instead of rendering
     * a link that 404s. Doesn't cache a failed mkdir() as "dir ensured", so the next call in the
     * same request tries again rather than assuming failure is permanent.
     *
     * @param string $name Filename prefix - an asset's uid, or a synthetic bundle name.
     */
    public function cacheMinifiedFile(string $name, string $content, string $extension): ?string
    {
        $dir = rtrim(EX_PATH, '/\\') . "/cache/assets/$extension";

        if (! isset(self::$ensuredCacheDirs[$dir])) {
            if (! is_dir($dir) && ! @mkdir($dir, 0755, true) && ! is_dir($dir)) {
                return null;
            }
            self::$ensuredCacheDirs[$dir] = true;
        }

        $file = sprintf('%s/%s-%s.%s', $dir, $this->sanitizeId($name), substr(sha1($content), 0, 12), $extension);

        if (! is_file($file) && @file_put_contents($file, $content, LOCK_EX) === false) {
            return null;
        }

        return self::toUrl($file);
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
                if (in_array($attribute, ['async', 'defer'], true)) {
                    if ($value) {
                        $result[] = $attribute;
                    }
                } elseif ($value) {
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
