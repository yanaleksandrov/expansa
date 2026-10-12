<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Support\Schema;
use Expansa\Facades\Lifecycle;
use Expansa\Hooks\Attributes\Priority;

final class Seo
{
    /**
     * Put the JSON-LD graph of the SEO settings before `</head>` of a public page;
     * runs last, when the theme has rendered the page.
     *
     * @param string $content Page HTML.
     * @param string $slug    Page slug, `welcome` for the home page.
     * @return string
     */
    #[Priority(Priority::LOW)]
    public function dashboardLoaded(string $content, string $slug): string
    {
        // the installed page is a dashboard screen shown in the web context
        if (! Lifecycle::is('web') || $slug === 'installed' || http_response_code() >= 400) {
            return $content;
        }

        $head = stripos($content, '</head>');
        if ($head === false) {
            return $content;
        }

        return substr_replace($content, Schema::render(), $head, 0);
    }
}
