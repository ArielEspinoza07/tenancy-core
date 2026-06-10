<?php

declare(strict_types=1);

namespace Tenancy\Contracts\Authorization;

use Tenancy\Contracts\Context\TenantContextInterface;

interface TenantPermissionCheckerInterface
{
    public function ensureCan(
        int|string $userId,
        TenantContextInterface $context,
        string $permission,
    ): void;

    public function can(
        int|string $userId,
        TenantContextInterface $context,
        string $permission,
    ): bool;
}
