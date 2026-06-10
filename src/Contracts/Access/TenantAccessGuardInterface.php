<?php

declare(strict_types=1);

namespace Tenancy\Contracts\Access;

use Tenancy\Contracts\Context\TenantContextInterface;

interface TenantAccessGuardInterface
{
    public function ensureAccess(
        int|string $userId,
        TenantContextInterface $context,
    ): void;

    public function canAccess(
        int|string $userId,
        TenantContextInterface $context,
    ): bool;
}
