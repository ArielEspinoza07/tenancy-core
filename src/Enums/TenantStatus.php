<?php

declare(strict_types=1);

namespace Tenancy\Enums;

enum TenantStatus: string
{
    case Active = 'active';
    case Pending = 'pending';
    case Suspended = 'suspended';
    case Deleted = 'deleted';

    public static function default(): self
    {
        return self::Active;
    }

    public function isActive(): bool
    {
        return $this === self::Active;
    }

    public function isPending(): bool
    {
        return $this === self::Pending;
    }

    public function isSuspended(): bool
    {
        return $this === self::Suspended;
    }

    public function isDeleted(): bool
    {
        return $this === self::Deleted;
    }
}
