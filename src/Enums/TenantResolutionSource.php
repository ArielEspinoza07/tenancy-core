<?php

declare(strict_types=1);

namespace Tenancy\Enums;

enum TenantResolutionSource: string
{
    case ApiKey = 'api_key';
    case CustomDomain = 'custom_domain';
    case Header = 'header';
    case Path = 'path';
    case Session = 'session';
    case Subdomain = 'subdomain';
    case System = 'system';

    public function isSystem(): bool
    {
        return $this === self::System;
    }

    public function isTenant(): bool
    {
        return $this !== self::System;
    }
}
