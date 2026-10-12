<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Option;
use App\Models\User;
use DateTimeImmutable;
use Expansa\Facades\Hook;

/**
 * JSON-LD graph of schema.org from the SEO settings: the website and its publisher,
 * an organization or a person. Google reads it for the Knowledge Graph and rich results.
 *
 * Plugins change the graph with the `seoSchema` hook:
 *
 * ```php
 * Hook::add('seoSchema', fn (array $graph) => [...$graph, ['@type' => 'Product', 'name' => 'Expansa']]);
 * ```
 *
 * @package App\Support
 */
final class Schema
{
    /**
     * Properties of schema.org by the keys of the organization identifiers in the SEO form.
     */
    private const array IDENTIFIERS = [
        'vat'   => 'vatID',
        'tax'   => 'taxID',
        'iso'   => 'iso6523Code',
        'duns'  => 'duns',
        'lei'   => 'leiCode',
        'naics' => 'naics',
    ];

    /**
     * Formats of the founding date the datepicker may give, by the order of preference.
     */
    private const array DATE_FORMATS = ['Y-m-d', 'd.m.Y', 'd/m/Y', 'm/d/Y'];

    /**
     * Get the `<script type="application/ld+json">` tag of the graph, empty without nodes.
     *
     * @return string
     */
    public static function render(): string
    {
        /**
         * Nodes of the JSON-LD graph printed on the public pages.
         *
         * @param array<int, array<string, mixed>> $graph
         */
        $graph = Hook::call('seoSchema', self::graph());
        if ($graph === []) {
            return '';
        }

        // JSON_HEX_TAG keeps "</script>" of a value from closing the tag
        $json = json_encode(
            ['@context' => 'https://schema.org', '@graph' => array_values($graph)],
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG
        );

        return '<script type="application/ld+json">' . $json . '</script>';
    }

    /**
     * Get the nodes of the graph: the website and, when its name is set, the publisher.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function graph(): array
    {
        $seo  = (array) Option::get('seo', []);
        $site = new Site();

        $publisher = ($seo['represents'] ?? 'organization') === 'person'
            ? self::person($site->url, (array) ($seo['person'] ?? []))
            : self::organization($site->url, (array) ($seo['organization'] ?? []));

        $website = self::filter([
            '@type'         => 'WebSite',
            '@id'           => $site->url . '#website',
            'url'           => $site->url,
            'name'          => $site->name,
            'alternateName' => self::text($seo['site']['alternate_name'] ?? ''),
            'description'   => self::text(Option::get('site.tagline')),
            'inLanguage'    => $site->locale,
            'publisher'     => $publisher === null ? null : ['@id' => $publisher['@id']],
        ]);

        return $publisher === null ? [$website] : [$website, $publisher];
    }

    /**
     * Get the organization node, null without its name.
     *
     * @param string               $url          Address of the site.
     * @param array<string, mixed> $organization Values of the SEO form.
     * @return array<string, mixed>|null
     */
    private static function organization(string $url, array $organization): ?array
    {
        $name = self::text($organization['name'] ?? '');
        if ($name === '') {
            return null;
        }

        $logo = self::image($url . '#logo', self::text($organization['logo'] ?? ''), $name);
        $node = [
            '@type'             => 'Organization',
            '@id'               => $url . '#organization',
            'name'              => $name,
            'alternateName'     => self::text($organization['alternate_name'] ?? ''),
            'url'               => $url,
            'logo'              => $logo,
            'image'             => $logo === null ? null : ['@id' => $logo['@id']],
            'sameAs'            => self::profiles($organization),
            'description'       => self::text($organization['description'] ?? ''),
            'email'             => self::text($organization['email'] ?? ''),
            'telephone'         => self::text($organization['phone'] ?? ''),
            'legalName'         => self::text($organization['legal_name'] ?? ''),
            'foundingDate'      => self::date(self::text($organization['founding_date'] ?? '')),
            'numberOfEmployees' => self::employees(self::text($organization['employees'] ?? '')),
        ];

        foreach (self::IDENTIFIERS as $key => $property) {
            $node[$property] = self::text($organization['identifiers'][$key] ?? '');
        }

        return self::filter($node);
    }

    /**
     * Get the node of the person who publishes the site, null without the user.
     *
     * @param string               $url    Address of the site.
     * @param array<string, mixed> $person Values of the SEO form.
     * @return array<string, mixed>|null
     */
    private static function person(string $url, array $person): ?array
    {
        $user = User::find((int) ($person['user'] ?? 0));
        if (! $user instanceof User) {
            return null;
        }

        $id    = $url . '#/schema/person/' . $user->id;
        $name  = $user->showname ?: $user->login;
        $image = self::image($id . '/image', self::text($person['image'] ?? ''), $name);

        return self::filter([
            '@type' => ['Person', 'Organization'],
            '@id'   => $id,
            'name'  => $name,
            'image' => $image,
            'logo'  => $image === null ? null : ['@id' => $image['@id']],
        ]);
    }

    /**
     * Get the addresses of the profiles of the organization: Facebook, X, Mastodon and the others.
     *
     * @param array<string, mixed> $organization Values of the SEO form.
     * @return list<string>
     */
    private static function profiles(array $organization): array
    {
        // X is set by a username or an address
        $x = self::text($organization['x'] ?? '');
        if ($x !== '' && ! str_contains($x, '/')) {
            $x = 'https://x.com/' . ltrim($x, '@');
        }

        $urls = [$organization['facebook'] ?? '', $x, $organization['mastodon'] ?? '', ...(array) ($organization['profiles'] ?? [])];
        $urls = array_filter(array_map(self::text(...), $urls), fn (string $url) => filter_var($url, FILTER_VALIDATE_URL) !== false);

        return array_values(array_unique($urls));
    }

    /**
     * Get the ImageObject node, null without the address.
     *
     * @param string $id      Identifier of the node.
     * @param string $url     Address of the image.
     * @param string $caption
     * @return array<string, string>|null
     */
    private static function image(string $id, string $url, string $caption): ?array
    {
        if ($url === '') {
            return null;
        }

        return ['@type' => 'ImageObject', '@id' => $id, 'url' => $url, 'contentUrl' => $url, 'caption' => $caption];
    }

    /**
     * Get the number of employees from a range of the SEO form: `11-50` or `5001` for "more than 5000".
     *
     * @param string $range
     * @return array<string, int|string>|null
     */
    private static function employees(string $range): ?array
    {
        if ($range === '') {
            return null;
        }

        [$min, $max] = explode('-', $range) + [1 => null];

        return self::filter(['@type' => 'QuantitativeValue', 'minValue' => (int) $min, 'maxValue' => $max === null ? null : (int) $max]);
    }

    /**
     * Get a date as ISO 8601, empty when no known format fits it.
     *
     * @param string $date
     * @return string
     */
    private static function date(string $date): string
    {
        foreach (self::DATE_FORMATS as $format) {
            $parsed = DateTimeImmutable::createFromFormat('!' . $format, $date);
            if ($parsed !== false && $parsed->format($format) === $date) {
                return $parsed->format('Y-m-d');
            }
        }

        return '';
    }

    /**
     * Get an option value as a trimmed string, empty for an array.
     *
     * @param mixed $value
     * @return string
     */
    private static function text(mixed $value): string
    {
        return is_scalar($value) ? trim((string) $value) : '';
    }

    /**
     * Drop the empty properties of a node: schema.org takes no empty values.
     *
     * @param array<string, mixed> $node
     * @return array<string, mixed>
     */
    private static function filter(array $node): array
    {
        return array_filter($node, fn ($value) => $value !== '' && $value !== null && $value !== []);
    }
}
