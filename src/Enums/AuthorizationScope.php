<?php

declare(strict_types=1);

namespace Tenancy\Enums;

enum AuthorizationScope: string
{
    case System = 'system';
    case Tenant = 'tenant';

    public function isSystem(): bool
    {
        return $this === self::System;
    }

    public function isTenant(): bool
    {
        return $this === self::Tenant;
    }
}
