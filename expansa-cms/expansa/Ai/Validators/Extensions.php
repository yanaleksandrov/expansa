<?php

declare(strict_types=1);

namespace Expansa\Ai\Validators;

use Expansa\Ai\Contracts\Validator;
use Expansa\Ai\Internal\Tokens;
use Expansa\Ai\Platform;

/**
 * Rejects calls to functions and classes of PHP extensions the platform does not have.
 * Symbols come from a map of common extensions: names ending with "*" are function prefixes.
 * Functions and classes declared in the generated files themselves are never reported.
 */
final class Extensions implements Validator
{
    /**
     * Functions and classes of common optional extensions, in lower case.
     */
    private const array SYMBOLS = [
        'apcu'      => ['functions' => ['apcu_*'], 'classes' => ['apcuiterator']],
        'bcmath'    => [
            'functions' => ['bcadd', 'bcsub', 'bcmul', 'bcdiv', 'bcmod', 'bcpow', 'bcpowmod', 'bcsqrt', 'bcscale', 'bccomp'],
        ],
        'bz2'       => ['functions' => ['bzopen', 'bzread', 'bzwrite', 'bzclose', 'bzcompress', 'bzdecompress']],
        'calendar'  => ['functions' => ['cal_*', 'jdtogregorian', 'gregoriantojd', 'easter_date', 'easter_days']],
        'ctype'     => ['functions' => ['ctype_*']],
        'curl'      => ['functions' => ['curl_*'], 'classes' => ['curlhandle', 'curlfile', 'curlstringfile']],
        'dom'       => [
            'functions' => ['dom_import_simplexml'],
            'classes'   => ['domdocument', 'domxpath', 'domelement', 'domnode'],
        ],
        'exif'      => ['functions' => ['exif_*']],
        'ffi'       => ['classes' => ['ffi']],
        'fileinfo'  => ['functions' => ['finfo_*', 'mime_content_type'], 'classes' => ['finfo']],
        'ftp'       => ['functions' => ['ftp_*']],
        'gd'        => [
            'functions' => [
                'gd_info', 'imagecreate*', 'imagecopy*', 'imagejpeg', 'imagepng', 'imagegif', 'imagewebp', 'imageavif',
                'imagedestroy', 'imagesx', 'imagesy', 'imagecolor*', 'imagefill*', 'imagettf*', 'imagescale', 'imagerotate',
                'imagestring*', 'imagefilter', 'imagecrop', 'imageflip',
            ],
            'classes'   => ['gdimage'],
        ],
        'gettext'   => ['functions' => ['gettext', 'ngettext', 'dgettext', 'dngettext', 'bindtextdomain', 'textdomain']],
        'gmp'       => ['functions' => ['gmp_*'], 'classes' => ['gmp']],
        'iconv'     => ['functions' => ['iconv', 'iconv_*', 'ob_iconv_handler']],
        'igbinary'  => ['functions' => ['igbinary_*']],
        'imagick'   => ['classes' => ['imagick', 'imagickdraw', 'imagickpixel']],
        'intl'      => [
            'functions' => [
                'intl_*', 'grapheme_*', 'idn_to_*', 'transliterator_*', 'numfmt_*', 'collator_*', 'datefmt_*', 'msgfmt_*',
                'normalizer_*', 'locale_*',
            ],
            'classes'   => [
                'collator', 'numberformatter', 'intldateformatter', 'messageformatter', 'normalizer', 'locale',
                'transliterator', 'intlchar', 'intltimezone', 'intlcalendar', 'spoofchecker', 'resourcebundle',
            ],
        ],
        'ldap'      => ['functions' => ['ldap_*']],
        'mbstring'  => ['functions' => ['mb_*']],
        'memcached' => ['classes' => ['memcached']],
        'mysqli'    => ['functions' => ['mysqli_*'], 'classes' => ['mysqli', 'mysqli_stmt', 'mysqli_result']],
        'openssl'   => ['functions' => ['openssl_*']],
        'pcntl'     => ['functions' => ['pcntl_*']],
        'pdo'       => ['classes' => ['pdo', 'pdostatement', 'pdoexception']],
        'posix'     => ['functions' => ['posix_*']],
        'redis'     => ['classes' => ['redis', 'rediscluster']],
        'shmop'     => ['functions' => ['shmop_*']],
        'simplexml' => ['functions' => ['simplexml_*'], 'classes' => ['simplexmlelement']],
        'soap'      => ['classes' => ['soapclient', 'soapserver', 'soapfault', 'soapvar']],
        'sockets'   => ['functions' => ['socket_*']],
        'sodium'    => ['functions' => ['sodium_*']],
        'sqlite3'   => ['classes' => ['sqlite3']],
        'tidy'      => ['functions' => ['tidy_*'], 'classes' => ['tidy']],
        'xml'       => ['functions' => ['xml_*']],
        'xmlreader' => ['classes' => ['xmlreader']],
        'xmlwriter' => ['functions' => ['xmlwriter_*'], 'classes' => ['xmlwriter']],
        'xsl'       => ['classes' => ['xsltprocessor']],
        'yaml'      => ['functions' => ['yaml_*']],
        'zip'       => ['functions' => ['zip_*'], 'classes' => ['ziparchive']],
        'zlib'      => [
            'functions' => [
                'zlib_*', 'gzopen', 'gzcompress', 'gzuncompress', 'gzencode', 'gzdecode', 'gzdeflate', 'gzinflate', 'gzfile',
                'deflate_*', 'inflate_*',
            ],
        ],
    ];

    /**
     * Tokens after which a name refers to a class.
     */
    private const array CLASS_CONTEXT = [T_NEW, T_INSTANCEOF, T_EXTENDS, T_IMPLEMENTS, T_USE];

    /**
     * Tokens that declare a class-like type.
     */
    private const array DECLARATIONS = [T_CLASS, T_INTERFACE, T_TRAIT, T_ENUM];

    /**
     * Stores the platform the generated code must run on.
     */
    public function __construct(

        /**
         * PHP version and available extensions.
         */
        public readonly Platform $platform = new Platform(),
    ) {}

    /**
     * Reports each function or class of an unavailable extension once per file.
     *
     * @param array<string, string> $files Relative paths mapped to source
     * @return string[] Validation errors, or an empty array
     */
    public function validate(array $files): array
    {
        $missing = array_diff_key(self::SYMBOLS, array_flip($this->platform->extensions));
        if ($missing === []) {
            return [];
        }

        $sources = [];
        $declared = [];
        foreach ($files as $path => $source) {
            if (strtolower(pathinfo((string) $path, PATHINFO_EXTENSION)) === 'php') {
                $sources[$path] = $tokens = Tokens::significant($source);
                $declared += $this->declarations($tokens);
            }
        }

        $errors = [];
        foreach ($sources as $path => $tokens) {
            foreach ($this->references($tokens) as $name => [$kind, $line, $original]) {
                $extension = isset($declared[$name]) ? null : $this->extension($name, $kind, $missing);
                if ($extension !== null) {
                    $errors[] = "{$path}:{$line}: {$original} needs the PHP extension \"{$extension}\", which is not "
                        . 'available. Use only the available extensions.';
                }
            }
        }

        return $errors;
    }

    /**
     * Collects names of functions and class-like types declared in one file.
     *
     * @param array<int, array{int|string, string, int}> $tokens Significant tokens
     * @return array<string, true> Declared names in lower case
     */
    private function declarations(array $tokens): array
    {
        $names = [];
        foreach ($tokens as $i => [$id]) {
            $isDeclaration = $id === T_FUNCTION || in_array($id, self::DECLARATIONS, true)
                && ($tokens[$i - 1][0] ?? null) !== T_DOUBLE_COLON;
            if ($isDeclaration && ($tokens[$i + 1][0] ?? null) === T_STRING) {
                $names[strtolower($tokens[$i + 1][1])] = true;
            }
        }

        return $names;
    }

    /**
     * Collects global function calls and class references with the line of their first use.
     *
     * @param array<int, array{int|string, string, int}> $tokens Significant tokens
     * @return array<string, array{string, int, string}> Lower-case name mapped to kind ("function" or "class"), line, and name
     */
    private function references(array $tokens): array
    {
        $references = [];
        foreach ($tokens as $i => [$id, $text, $line]) {
            if (! in_array($id, [T_STRING, T_NAME_FULLY_QUALIFIED], true) || str_contains(ltrim($text, '\\'), '\\')) {
                continue;
            }

            $kind = match (true) {
                Tokens::isFunctionCall($tokens, $i) => 'function',
                in_array($tokens[$i - 1][0] ?? null, self::CLASS_CONTEXT, true),
                ($tokens[$i + 1][0] ?? null) === T_DOUBLE_COLON => 'class',
                default => null,
            };
            if ($kind !== null) {
                $references[Tokens::name($text)] ??= [$kind, $line, ltrim($text, '\\')];
            }
        }

        return $references;
    }

    /**
     * Finds the unavailable extension that provides a symbol.
     *
     * @param string $name Lower-case symbol name
     * @param string $kind "function" or "class"
     * @param array<string, array{functions?: string[], classes?: string[]}> $missing Symbols of unavailable extensions
     * @return string|null Extension name, or null when no unavailable extension provides the symbol
     */
    private function extension(string $name, string $kind, array $missing): ?string
    {
        $key = $kind === 'function' ? 'functions' : 'classes';
        foreach ($missing as $extension => $symbols) {
            foreach ($symbols[$key] ?? [] as $symbol) {
                $matches = str_ends_with($symbol, '*') ? str_starts_with($name, substr($symbol, 0, -1)) : $symbol === $name;
                if ($matches) {
                    return $extension;
                }
            }
        }

        return null;
    }
}
