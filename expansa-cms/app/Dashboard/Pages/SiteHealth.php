<?php

declare(strict_types=1);

namespace App\Dashboard\Pages;

use App\Support\Dates;
use App\Support\SiteHealth as Checks;
use App\Support\Values\Section;

/**
 * Site health: server, security, database, storage, extensions and AI checks.
 *
 * @package App\Dashboard
 */
final class SiteHealth
{
    /**
     * Data of the `site-health` page: the sections with their status, the number of checks by status,
     * the status of the site, the first section with a problem, open on load, and the formatted time
     * of the network checks; `?recheck` runs them again.
     *
     * @return array<string, mixed>
     */
    public static function data(): array
    {
        $probes = Checks::probes(isset($_GET['recheck']));
        $groups = Checks::groups($probes);
        $count  = Checks::count($groups);

        return [
            'groups'  => $groups,
            'count'   => $count,
            'checked' => Dates::format($probes['time']),
            'status'  => match (true) {
                $count[Checks::CRITICAL] > 0    => Checks::CRITICAL,
                $count[Checks::RECOMMENDED] > 0 => Checks::RECOMMENDED,
                default                         => Checks::GOOD,
            },
            'open'    => array_find($groups, fn (Section $group) => $group->status !== Checks::GOOD)->id ?? '',
        ];
    }
}
