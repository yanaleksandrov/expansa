<?php

declare(strict_types=1);

namespace Expansa\Database\Model;

use DateTime;

trait HasTimestamps
{
    /**
     * Column creation is stamped in.
     *
     * @var string
     */
    protected string $createdAt = 'created_at';

    /**
     * Column the last update is stamped in.
     *
     * @var string
     */
    protected string $updatedAt = 'updated_at';

    /**
     * Whether this model tracks timestamps at all. Not currently read anywhere - kept as a
     * per-model override point for a future opt-out.
     *
     * @var bool
     */
    protected bool $timestamps = true;

    /**
     * Exposes the raw stored timestamp as a DateTime - same pattern as e.g. User's own
     * verificationTokenExpiresAt(). A using class overriding createdAt()/updatedAt() itself
     * (e.g. Apikey, which formats it into a display string instead) wins over this one; PHP
     * resolves a class's own method before falling back to a trait's.
     *
     * @return Attribute
     */
    protected function createdAt(): Attribute
    {
        return Attribute::make(get: fn($value) => $value ? new DateTime($value) : null);
    }

    /**
     * @see self::createdAt()
     * @return Attribute
     */
    protected function updatedAt(): Attribute
    {
        return Attribute::make(get: fn($value) => $value ? new DateTime($value) : null);
    }
}
