<?php

declare(strict_types=1);

namespace App\Post;

class Status
{
    /**
     * Register a post status; not implemented yet, statuses are fixed.
     *
     * @param mixed ...$data Name and options of the status.
     * @return void
     */
    public static function register(mixed ...$data): void
    {
    }

    public static function get(string $status = '', array $args = []): string
    {
        return '';
    }

    /**
     * Get the status a post takes by its date: a published one dated ahead is scheduled, a scheduled one
     * whose date has come is published; the other statuses do not depend on the date.
     *
     * @param string $status
     * @param string $date   Date and time of the post, `Y-m-d H:i:s`.
     * @return string
     */
    public static function byDate(string $status, string $date): string
    {
        $ahead = $date !== '' && strtotime($date) > time();

        return match (true) {
            $status === 'publish' && $ahead  => 'future',
            $status === 'future' && ! $ahead => 'publish',
            default                          => $status,
        };
    }

    public static function getStatuses(): array
    {
        return [
            'publish'   => t('Published'),
            'pending'   => t('Pending'),
            'draft'     => t('Draft'),
            'protected' => t('Protected'),
            'private'   => t('Private'),
            'future'    => t('Scheduled'),
        ];
    }
}
