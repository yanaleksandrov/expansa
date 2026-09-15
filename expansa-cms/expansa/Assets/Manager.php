<?php

declare(strict_types=1);

namespace Expansa\Assets;

use Closure;
use Expansa\Assets\Abstracts\Provider;
use Expansa\Assets\Support\AssetHandler;
use Expansa\Assets\Providers\Link;
use Expansa\Assets\Providers\Script;
use InvalidArgumentException;

/**
 * Allows assets (CSS, JS, etc.) to be included throughout the application, and then outputted
 * later based on dependencies. This makes sure all assets will be included in the correct
 * order, no matter what order they are defined in.
 */
class Manager
{
    use AssetHandler;

    /**
     * Assets list, keyed by composite id ("{uid}-{extension}").
     *
     * @var array<string, Provider>
     */
    public static array $assets = [];

    /**
     * Provider registry: file extension (or arbitrary provider key) => provider class.
     * Register your own with {@see self::provider()} to support new asset kinds
     * (fonts, preloads, importmaps, ...) without touching this class.
     *
     * @var array<string, class-string<Provider>>
     */
    protected static array $providers = [
        'css' => Link::class,
        'js'  => Script::class,
    ];

    /**
     * Template file extensions stripped when resolving the default co-located structure
     * in {@see self::defaultStructure()}.
     */
    protected const array TEMPLATE_EXTENSIONS = ['blade.php', 'php', 'html'];

    /**
     * Resolver used by {@see self::discover()} to find a file's CSS/JS. Receives the absolute
     * path of the file being rendered and the `$context` passed to discover(), and returns a
     * map of provider key => candidate absolute path, e.g.
     * `['css' => '/path/to/style.css', 'js' => '/path/to/script.js']`.
     *
     * @var null|Closure(string, array): array<string, string>
     */
    protected static ?Closure $configure = null;

    /**
     * Per-(file, uid, context) memo of {@see self::discover()}'s resolved candidates, so
     * discovering the same file repeatedly (e.g. one field template rendered many times on a
     * form) stats the disk once and replays the result. Assumes {@see self::configure()} isn't
     * swapped mid-request.
     *
     * @var array<string, array<string, string>>
     */
    protected static array $discovered = [];

    /** sortDependencies(self::$assets), cached until {@see self::$assetsRevision} changes. */
    private static ?array $sorted = null;

    /** Bumped by {@see self::enqueue()}/{@see self::dequeue()} on an actual change (not a dedup no-op). */
    private static int $assetsRevision = 0;

    /** Revision {@see self::$sorted} was computed at. */
    private static int $sortedRevision = -1;

    /**
     * Register (or override) the provider responsible for a given extension/provider key.
     * This is the extension point that keeps asset support open-ended: plugins and themes
     * can teach the manager new kinds of assets without modifying it.
     *
     * @param string                 $extension     e.g. 'css', 'js', or a custom key such as 'font'.
     * @param class-string<Provider> $providerClass Must extend {@see Provider}.
     */
    public static function provider(string $extension, string $providerClass): void
    {
        if (! is_a($providerClass, Provider::class, true)) {
            throw new InvalidArgumentException(sprintf('%s must extend %s', $providerClass, Provider::class));
        }

        self::$providers[$extension] = $providerClass;
    }

    /**
     * Replace {@see self::defaultStructure()} as the way {@see self::discover()} finds a file's
     * CSS/JS. The resolver gets the file's absolute path plus discover()'s `$context` (for
     * telling apart several things that render through the same file), and returns a map of
     * provider key => candidate absolute path - see documentation/Asset.md for worked examples.
     *
     * Applies to *every* discover() call app-wide, so a resolver that only cares about some
     * files should fall back to {@see self::defaultStructure()} for the rest rather than
     * returning an empty array.
     *
     * @param Closure(string, array): array<string, string> $resolver
     */
    public static function configure(Closure $resolver): void
    {
        self::$configure = $resolver;
    }

    /**
     * Correctly add JS scripts and CSS styles to the page.
     * To connect files, it is better to use this function than to specify the path to the file directly.
     * This will allow you to combine JS or CSS files into one without any problems.
     * Get rid of script conflicts when the dependent script is connected to the main one.
     *
     * @param string $uid      Unique ID of the resource.
     * @param string $src      Full URL of the resource, or path of the script relative to the Expansa root directory.
     * @param string $provider Provider key registered via {@see self::provider()} (built-in: 'css', 'js').
     * @param array  $data     List of attributes.
     * @return void
     */
    public function enqueue(string $uid, string $src, string $provider, ...$data): void
    {
        $uid = $this->sanitizeId($uid);
        $src = $this->sanitizeUrl($src);

        if ($uid === '' || ! isset(self::$providers[$provider])) {
            return;
        }

        // Skip constructing (and thus toPath() stat-ing the disk) an asset that's already
        // registered - matters because discover() re-enqueues the same uid often per request.
        if (isset(self::$assets[$uid . '-' . pathinfo($src, PATHINFO_EXTENSION)])) {
            return;
        }

        $asset = new (self::$providers[$provider])($uid, $src, ...$data);

        if (! isset(self::$assets[$asset->id])) {
            self::$assets[$asset->id] = $asset;
            self::$assetsRevision++;
        }
    }

    /**
     * Correctly add scripts.
     *
     * @param string $uid Unique ID of the resource.
     * @param string $src
     * @param array $data
     * @return void
     */
    public function script(string $uid, string $src, array ...$data): void
    {
        $this->enqueue($uid, $src, 'js', ...$data);
    }

    /**
     * Correctly add styles.
     *
     * @param string $uid Unique ID of the resource.
     * @param string $src
     * @param array $data
     * @return void
     */
    public function style(string $uid, string $src, array ...$data): void
    {
        $this->enqueue($uid, $src, 'css', ...$data);
    }

    /**
     * Replace the source (and, optionally, the attributes) of an already-known asset,
     * e.g. to swap a bundled library for a CDN copy.
     *
     * @param string      $uid      Unique ID of the resource.
     * @param string      $src      New URL of the resource.
     * @param string|null $provider Provider key. Defaults to $src's own extension when that's a
     *                              registered provider, falling back to the uid's current one
     *                              (e.g. for a CDN URL with no recognizable extension).
     * @param array       $data
     * @return void
     */
    public function override(string $uid, string $src, ?string $provider = null, ...$data): void
    {
        $uid = $this->sanitizeId($uid);

        if ($provider === null) {
            $extension = pathinfo($this->sanitizeUrl($src), PATHINFO_EXTENSION);
            $provider  = isset(self::$providers[$extension]) ? $extension : ($this->providerOf($uid) ?? $extension);
        }

        // Only the uid's registration for *this* provider is replaced - a uid commonly has both
        // a style() and a script() registered under it, and overriding one shouldn't drop the
        // other.
        $this->dequeue($uid, $provider);
        $this->enqueue($uid, $src, $provider, ...$data);
    }

    /**
     * Remove a previously enqueued source.
     *
     * @param string      $uid      The unique id of the asset which to be deleted.
     * @param string|null $provider Limit removal to this provider - a uid may have more than
     *                              one (a style and a script sharing the same uid).
     * @return void
     */
    public function dequeue(string $uid, ?string $provider = null): void
    {
        $class = $provider !== null ? (self::$providers[$provider] ?? null) : null;

        $before = self::$assets;

        self::$assets = array_filter(
            self::$assets,
            fn (Provider $asset) => $asset->uid !== $uid || ($class !== null && $asset::class !== $class)
        );

        if (self::$assets !== $before) {
            self::$assetsRevision++;
        }
    }

    /**
     * Get enqueued assets.
     *
     * @param string $uid When given, only assets registered under this uid (there may be
     *                    more than one, e.g. a css and a js asset sharing the same uid).
     * @return array<string, Provider>
     */
    public function get(string $uid = ''): array
    {
        if ($uid === '') {
            return self::$assets;
        }

        return array_filter(self::$assets, fn (Provider $asset) => $asset->uid === $uid);
    }

    /**
     * Auto-connect the CSS/JS belonging to an arbitrary file (typically a template being
     * rendered) - no dependency on the view layer or anything else. Where those files actually
     * live is up to {@see self::configure()}; by default it's $file's own directory, under its
     * own basename (`card.blade.php` -> `card.css` + `card.js`).
     *
     * Safe to call repeatedly: assets are deduplicated by id, so one file contributes its
     * CSS/JS only once no matter how many times it's discovered.
     *
     * @param string      $file    Absolute path of the file being rendered.
     * @param string|null $uid     Uid to register discovered assets under. Defaults to the
     *                             file's basename, so repeated calls for the same file merge.
     * @param array       $context Forwarded as-is to the {@see self::configure()} resolver's
     *                             second argument, for cases $file alone can't disambiguate.
     * @return void
     */
    public function discover(string $file, ?string $uid = null, array $context = []): void
    {
        $uid ??= $this->sanitizeId(basename(self::stripTemplateExtension($file)));

        $key = $file . "\0" . $uid . "\0" . serialize($context);

        if (! isset(self::$discovered[$key])) {
            self::$discovered[$key] = $this->resolveDiscoverable($file, $context);
        }

        foreach (self::$discovered[$key] as $extension => $path) {
            $this->enqueue($uid, $this->toUrl($path), $extension);
        }
    }

    /**
     * The actual (filesystem-touching) resolution behind {@see self::discover()}, split out so
     * it only ever runs once per distinct (file, uid, context) - see {@see self::$discovered}.
     *
     * @return array<string, string> extension => path, only candidates that exist on disk.
     */
    private function resolveDiscoverable(string $file, array $context): array
    {
        if (! is_file($file)) {
            return [];
        }

        $candidates = self::$configure ? (self::$configure)($file, $context) : self::defaultStructure($file);

        $found = [];
        foreach ($candidates as $extension => $path) {
            if ($path !== $file && isset(self::$providers[$extension]) && is_file($path)) {
                $found[$extension] = $path;
            }
        }

        return $found;
    }

    /**
     * Built-in structure used by {@see self::discover()} when {@see self::configure()} hasn't
     * been configured: one candidate per registered provider, co-located with $file and
     * sharing its basename (template extension stripped).
     *
     * Public so a custom resolver passed to {@see self::configure()} can fall back to it for
     * files it doesn't specifically care about, instead of having to reimplement it or
     * disabling the convention for the rest of the application.
     *
     * @return array<string, string>
     */
    public static function defaultStructure(string $file): array
    {
        $base = self::stripTemplateExtension($file);

        $paths = [];
        foreach (array_keys(self::$providers) as $extension) {
            $paths[$extension] = "$base.$extension";
        }

        return $paths;
    }

    /**
     * Strip a known template extension (`.blade.php`, `.php`, `.html`) off $file, if present.
     */
    public static function stripTemplateExtension(string $file): string
    {
        foreach (self::TEMPLATE_EXTENSIONS as $extension) {
            if (str_ends_with($file, ".$extension")) {
                return substr($file, 0, -strlen($extension) - 1);
            }
        }

        return $file;
    }

    /**
     * Render HTML tags for the enqueued assets. See documentation/Asset.md for the full
     * combine/minify/inline write-up and examples.
     *
     * @param array $filter  Property name => expected value or `*`-pattern, e.g.
     *                       ['uid' => 'notifications', 'path' => '*.js', 'toFooter' => true].
     * @param bool  $combine Bundle adjacent same-provider, same-placement, local-file assets into
     *                       one unit instead of one tag each. An external URL always renders on
     *                       its own and breaks the current run; a run of just one asset renders
     *                       normally too (bundling needs two or more).
     * @param bool  $minify  Run each asset's own minify() over its content first.
     * @param bool  $inline  Embed the (possibly minified) content directly instead of linking to
     *                       it; with `combine`, the whole bundle is embedded and no cache file is
     *                       written. `$minify`/`$inline` are independent - any combination is valid.
     * @return void
     */
    public function render(array $filter = [], bool $combine = false, bool $minify = false, bool $inline = false): void
    {
        $assets = $this->filter($filter);

        if (! $combine) {
            foreach ($assets as $asset) {
                echo $asset->render($asset, $minify, $inline);
            }
            return;
        }

        /** @var array{class: class-string<Provider>, toFooter: bool, assets: Provider[]}|null $run */
        $run = null;

        $flush = function () use (&$run, $minify, $inline): void {
            if ($run === null) {
                return;
            }

            echo count($run['assets']) > 1
                ? $this->renderCombined($run, $minify, $inline)
                : $run['assets'][0]->render($run['assets'][0], $minify, $inline);

            $run = null;
        };

        foreach ($assets as $asset) {
            $combinable = $this->isCombinable($asset);

            if ($combinable && $run && $run['class'] === $asset::class && $run['toFooter'] === $asset->toFooter) {
                $run['assets'][] = $asset;
                continue;
            }

            $flush();

            if ($combinable) {
                $run = ['class' => $asset::class, 'toFooter' => $asset->toFooter, 'assets' => [$asset]];
            } else {
                echo $asset->render($asset, $minify, $inline);
            }
        }

        $flush();
    }

    /**
     * Whether $asset may join a combine() run: it needs a local file to read, and it must not
     * carry any property customized away from that provider's constructor defaults (async,
     * integrity, crossorigin, a custom `class`, ...) - a bundle is a fresh instance built from
     * just uid/src/toFooter, so none of that would survive being merged; asking for combine()
     * shouldn't silently drop it, so such an asset renders on its own instead. `data` is exempt
     * from this: {@see self::renderCombined()} carries every piece's preamble() (Script's data
     * dump) forward regardless of whether it joins a bundle.
     */
    private function isCombinable(Provider $asset): bool
    {
        if ($asset->path === '' || pathinfo($asset->path, PATHINFO_EXTENSION) === '') {
            return false;
        }

        static $defaults = [];
        $class = $asset::class;
        if (! isset($defaults[$class])) {
            $defaults[$class] = [];
            foreach (new \ReflectionMethod($class, '__construct')->getParameters() as $param) {
                if ($param->isDefaultValueAvailable()) {
                    $defaults[$class][$param->getName()] = $param->getDefaultValue();
                }
            }
        }

        $ignore = ['uid', 'href', 'src', 'path', 'id', 'data', 'dependencies', 'toFooter', 'version'];
        foreach (get_object_vars($asset) as $property => $value) {
            if (in_array($property, $ignore, true)) {
                continue;
            }
            if (array_key_exists($property, $defaults[$class]) && $value !== $defaults[$class][$property]) {
                return false;
            }
        }

        return true;
    }

    /**
     * Concatenate a run of same-provider, same-placement assets into one unit and render it:
     * embedded directly ($inline, nothing touches disk) or written to a cached file and linked
     * to. Uses readContent() per piece so a bundle shares the APCu minify cache with individual
     * renders instead of bypassing it.
     *
     * The bundle is always named just "bundle" - uniqueness/dedup comes entirely from the
     * content hash in the cache filename (see cacheMinifiedFile()), so two pages producing the
     * same concatenated content share one file regardless of which uids went into it. That still
     * depends on the pieces landing in the same order; a different dependency order still
     * produces different (correct, just not deduped) content and thus a different file.
     *
     * Each piece's preamble() (e.g. Script's `data` dump) is rendered too, ahead of the bundle
     * tag - the *bundle* itself is a fresh instance with no data of its own, but the pieces it
     * was built from might. Kept out of the bundle file itself; see preamble()'s own docs for why.
     *
     * @param array{class: class-string<Provider>, toFooter: bool, assets: Provider[]} $run
     */
    private function renderCombined(array $run, bool $minify, bool $inline): string
    {
        $content = implode("\n", array_map(
            fn (Provider $asset) => $this->readContent($asset, $minify) ?? '',
            $run['assets']
        ));

        $preamble = implode('', array_map(fn (Provider $asset) => $asset->preamble($asset), $run['assets']));

        $uid       = 'bundle';
        $extension = pathinfo($run['assets'][0]->path, PATHINFO_EXTENSION);

        if ($inline) {
            $bundle = new ($run['class'])($uid, "$uid.$extension", toFooter: $run['toFooter']);

            return $preamble . $bundle->renderInline($bundle, $content);
        }

        $url = $this->cacheMinifiedFile($uid, $content, $extension);

        if ($url === null) {
            // Couldn't write the bundle (disk full, permissions, ...) - fall back to each piece
            // on its own rather than a tag pointing at a file that doesn't exist. Each one goes
            // through its own render(), which has the same fallback for a plain (non-combined)
            // minify: true write failure, and renders its own preamble() already.
            return implode('', array_map(
                fn (Provider $asset) => $asset->render($asset, $minify, false),
                $run['assets']
            ));
        }

        $bundle = new ($run['class'])($uid, $url, toFooter: $run['toFooter']);

        return $preamble . $bundle->render($bundle);
    }

    /**
     * Delete cached/minified/combined files (everything {@see AssetHandler::cacheMinifiedFile()}
     * writes) older than $maxAge seconds, then - if $maxBytes is given - keep deleting the
     * oldest survivors (oldest mtime first) until the total is back under budget. Content
     * changes already get a fresh hash/filename on their own; this is purely about reclaiming
     * disk space from files no longer referenced by anything.
     *
     * Age/size-based, not reference-based (nothing on disk says whether a file is still "in
     * use"): run periodically (`php artisan asset:clean`, a cron/deploy step) with $maxAge
     * comfortably longer than your deploy cycle.
     *
     * @return int Number of files removed.
     */
    public static function clean(int $maxAge = 604800, ?int $maxBytes = null): int
    {
        $dir = rtrim(EX_PATH, '/\\') . '/cache/assets';

        if (! is_dir($dir)) {
            return 0;
        }

        $cutoff  = time() - $maxAge;
        $removed = 0;
        $kept    = [];

        foreach (glob($dir . '/*/*') ?: [] as $file) {
            if (! is_file($file)) {
                continue;
            }

            $mtime = filemtime($file);
            if ($mtime !== false && $mtime < $cutoff) {
                if (unlink($file)) {
                    $removed++;
                }
                continue;
            }

            $size = filesize($file);
            if ($mtime !== false && $size !== false) {
                $kept[$file] = ['mtime' => $mtime, 'size' => $size];
            }
        }

        if ($maxBytes === null) {
            return $removed;
        }

        $total = array_sum(array_column($kept, 'size'));
        if ($total <= $maxBytes) {
            return $removed;
        }

        uasort($kept, fn (array $a, array $b) => $a['mtime'] <=> $b['mtime']);

        foreach ($kept as $file => $info) {
            if ($total <= $maxBytes) {
                break;
            }
            if (unlink($file)) {
                $removed++;
                $total -= $info['size'];
            }
        }

        return $removed;
    }

    /**
     * Shared filtering logic behind {@see self::render()}. Sorts (and caches the sort of)
     * self::$assets, then applies $filter - see {@see self::$sorted}.
     *
     * @return array<string, Provider>
     */
    private function filter(array $filter): array
    {
        if (self::$sorted === null || self::$sortedRevision !== self::$assetsRevision) {
            self::$sorted         = $this->sortDependencies(self::$assets);
            self::$sortedRevision = self::$assetsRevision;
        }

        return array_filter(self::$sorted, function (Provider $asset) use ($filter) {
            foreach ($filter as $key => $value) {
                if (! property_exists($asset, $key)) {
                    continue;
                }

                if (is_string($value) && str_contains($value, '*')) {
                    if (! fnmatch($value, (string) $asset->$key)) {
                        return false;
                    }
                } elseif ($asset->$key !== $value) {
                    return false;
                }
            }

            return true;
        });
    }

    /**
     * Find which provider key an already-enqueued uid is using, if any.
     */
    private function providerOf(string $uid): ?string
    {
        foreach (self::$assets as $asset) {
            if ($asset->uid === $uid) {
                $key = array_search($asset::class, self::$providers, true);

                return $key !== false ? $key : null;
            }
        }

        return null;
    }
}
