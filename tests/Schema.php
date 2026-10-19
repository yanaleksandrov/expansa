<?php

declare(strict_types=1);

use App\Listeners\Seo;
use App\Models\Option;
use App\Support\Schema;
use Expansa\Facades\Hook;

// run: php tests/Schema.php
require_once __DIR__ . '/bootstrap.php';
require_once EX_PATH . 'expansa/functions.php';

$options = new ReflectionProperty(Option::class, 'options');
$seed    = fn (array $values) => $options->setValue(null, $values);

// JSON-LD: the website and the organization from the SEO settings
$seed([
    'site' => ['name' => 'amfAR', 'tagline' => 'Making AIDS History'],
    'seo'  => [
        'represents'   => 'organization',
        'site'         => ['alternate_name' => 'AMFAR'],
        'organization' => [
            'name'          => 'amfAR, The Foundation for AIDS Research',
            'logo'          => 'https://example.com/logo.png',
            'facebook'      => 'https://www.facebook.com/amfar/',
            'x'             => '@amfAR',
            'mastodon'      => '',
            'profiles'      => ['https://www.youtube.com/c/amFAR', 'not a url', 'https://www.facebook.com/amfar/'],
            'founding_date' => '1985-09-23',
            'employees'     => '51-200',
            'identifiers'   => ['vat' => 'US123', 'tax' => ''],
            'email'         => '',
        ],
    ],
]);

[$website, $organization] = Schema::graph();
$url = url();

check('the website takes the name, the alternate name and the tagline of the site', $website['name'] === 'amfAR' && $website['alternateName'] === 'AMFAR' && $website['description'] === 'Making AIDS History');
check('the website is published by the organization', $website['publisher'] === ['@id' => $url . '#organization'] && $organization['@id'] === $url . '#organization');
check('the logo is an ImageObject, the image refers to it', $organization['logo']['@type'] === 'ImageObject' && $organization['image'] === ['@id' => $url . '#logo']);
check('profiles get X from a username, drop invalid and repeated addresses', $organization['sameAs'] === ['https://www.facebook.com/amfar/', 'https://x.com/amfAR', 'https://www.youtube.com/c/amFAR']);
check('the founding date stays ISO 8601', $organization['foundingDate'] === '1985-09-23');
check('a range of employees becomes a QuantitativeValue', $organization['numberOfEmployees'] === ['@type' => 'QuantitativeValue', 'minValue' => 51, 'maxValue' => 200]);
check('identifiers become properties of schema.org', $organization['vatID'] === 'US123' && ! isset($organization['taxID']));
check('empty values are dropped', ! isset($organization['email']) && ! array_key_exists('description', $organization));

// without the organization name only the website is left
$seed(['site' => ['name' => 'amfAR'], 'seo' => ['represents' => 'organization', 'organization' => ['name' => ' ']]]);
$graph = Schema::graph();
check('without a publisher name the graph has the website only', count($graph) === 1 && ! isset($graph[0]['publisher']));

$seed(['site' => ['name' => 'a</script><b>'], 'seo' => []]);
$html = Schema::render();
check('render() prints a JSON-LD script tag', str_starts_with($html, '<script type="application/ld+json">{"@context":"https://schema.org","@graph":['));
check('a value cannot close the script tag', ! str_contains(substr($html, 0, -9), '</script>'));

Hook::add('seoSchema', fn (array $graph) => [...$graph, ['@type' => 'Thing', 'name' => 'Extra']]);
check('the seoSchema hook adds nodes', str_contains(Schema::render(), '{"@type":"Thing","name":"Extra"}'));

// the listener leaves pages outside the web context alone
check('the listener skips a page outside the web context', new Seo()->dashboardLoaded('<head></head>', 'welcome') === '<head></head>');

exit($failures ? 1 : 0);
