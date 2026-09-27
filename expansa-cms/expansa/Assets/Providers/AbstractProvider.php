<?php

declare(strict_types=1);

namespace Expansa\Assets\Providers;

use Expansa\Support\Url;

/**
 * Base of every asset provider: Link, Script or a custom one registered via Manager::provider().
 * A provider object is one enqueued asset; it renders its own tag and reads, minifies and caches
 * its local file. `$minify` and `$inline` come from the Manager::render() call, they are not stored.
 *
 * @package Expansa\Assets\Providers
 */
abstract class AbstractProvider
{
    /**
     * Above this size readContent() does not store the minified content in APCu: a large blob
     * fragments the shared segment and evicts other entries.
     */
    private const int APCU_MAX_BYTES = 1024 * 1024;

    /**
     * Unique id of the resource, as passed to Manager::enqueue().
     */
    abstract public string $uid { get; }

    /**
     * Key in the queue, unique per provider: `{uid}-{extension}`.
     */
    abstract public string $id { get; }

    /**
     * Absolute path of the local file, empty for an external URL.
     */
    abstract public string $path { get; }

    /**
     * Uids or ids of the assets rendered before this one.
     *
     * @var string[]
     */
    abstract public array $dependencies { get; }

    /**
     * Whether the asset renders before `</body>` instead of the head.
     */
    abstract public bool $toFooter { get; }

    /**
     * Render the tag of the asset.
     *
     * @param bool $minify Run minify() over the content of the local file first.
     * @param bool $inline Embed the content instead of linking to the file; no effect for an external URL.
     * @return string
     */
    abstract public function render(bool $minify = false, bool $inline = false): string;

    /**
     * Minify the content of the asset, return it unchanged when it can not be minified.
     *
     * @param string $code
     * @return string
     */
    abstract public function minify(string $code): string;

    /**
     * Wrap ready content in an inline tag, used for a combined bundle that has no file to read.
     * Falls back to render() for assets that can not be inlined, like fonts.
     *
     * @param string $content
     * @return string
     */
    public function renderInline(string $content): string
    {
        return $this->render();
    }

    /**
     * Markup rendered before the tag and kept out of cached and combined files,
     * so per-request data (Script `data`) does not change their hash.
     *
     * @return string
     */
    public function preamble(): string
    {
        return '';
    }

    /**
     * Read the local file, minified with $minify; null without a local file.
     * With APCu the minified content is cached by path and mtime between requests.
     *
     * @param bool $minify
     * @return string|null
     */
    public function readContent(bool $minify): ?string
    {
        if ($this->path === '') {
            return null;
        }

        if (! $minify) {
            $content = @file_get_contents($this->path);

            return $content === false ? null : $content;
        }

        $cacheKey = null;
        if (function_exists('apcu_fetch')) {
            $mtime = @filemtime($this->path);

            if ($mtime !== false) {
                $cacheKey = 'expansa:asset:minify:1:' . static::class . ':' . $this->path . ':' . $mtime;

                $cached = apcu_fetch($cacheKey, $success);
                if ($success) {
                    return $cached;
                }
            }
        }

        $content = @file_get_contents($this->path);
        if ($content === false) {
            return null;
        }

        $minified = $this->minify($content);

        if ($cacheKey !== null && strlen($minified) <= self::APCU_MAX_BYTES) {
            apcu_store($cacheKey, $minified, 86400);
        }

        return $minified;
    }

    /**
     * Write content to `cache/assets/{extension}/{name}-{hash}.{extension}` and get its URL.
     * The content hash in the name changes the URL on every change, so no `?ver=` is needed.
     *
     * @param string $name      Sanitized file name prefix: an asset uid or `bundle`.
     * @param string $content
     * @param string $extension
     * @return string|null Null if the file can not be written, the caller keeps the original URL.
     */
    public static function writeCache(string $name, string $content, string $extension): ?string
    {
        static $directories = [];

        $directory = Url::toPath("cache/assets/$extension");

        if (! isset($directories[$directory])) {
            if (! is_dir($directory) && ! @mkdir($directory, 0755, true) && ! is_dir($directory)) {
                return null;
            }
            $directories[$directory] = true;
        }

        $file = sprintf('%s/%s-%s.%s', $directory, $name, substr(sha1($content), 0, 12), $extension);

        if (! is_file($file) && @file_put_contents($file, $content, LOCK_EX) === false) {
            return null;
        }

        return Url::toUrl($file);
    }

    /**
     * Get the absolute path of a local asset URL, empty if the file does not exist (a CDN URL).
     *
     * @param string $url
     * @return string
     */
    public static function toPath(string $url): string
    {
        $file = Url::toPath((string) parse_url($url, PHP_URL_PATH));

        return is_file($file) ? $file : '';
    }

    /**
     * Get the public URL of a local file, the inverse of toPath().
     *
     * @param string $path
     * @return string
     */
    public static function toUrl(string $path): string
    {
        return Url::toUrl($path);
    }

    /**
     * Turn a uid into a camelCase JS variable name: `my-app_data` → `myAppData`.
     *
     * @param string $uid
     * @return string
     */
    public static function variableName(string $uid): string
    {
        return lcfirst(str_replace(' ', '', ucwords(str_replace(['_', '-'], ' ', trim($uid)))));
    }

    /**
     * Render tag attributes: `id`, `type`, `rel` first, empty values and non-scalars skipped.
     *
     * @param array<string, mixed> $attributes
     * @return string Attributes with a leading space, or an empty string.
     */
    public function renderAttributes(array $attributes): string
    {
        $result = [];

        $attributes = array_merge(
            array_intersect_key($attributes, array_flip(['id', 'type', 'rel'])),
            array_diff_key($attributes, array_flip(['id', 'type', 'rel']))
        );

        foreach ($attributes as $attribute => $value) {
            if (! is_scalar($value)) {
                continue;
            }

            $attribute = trim(htmlspecialchars((string) $attribute, ENT_QUOTES, 'UTF-8'));
            $value     = trim(htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'));
            if (! $attribute || ! $value) {
                continue;
            }

            $result[] = in_array($attribute, ['async', 'defer'], true) ? $attribute : sprintf('%s="%s"', $attribute, $value);
        }

        return $result ? ' ' . implode(' ', $result) : '';
    }
}
