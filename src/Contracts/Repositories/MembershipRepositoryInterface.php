<?php

declare(strict_types=1);

namespace Tenancy\Contracts\Repositories;

interface MembershipRepositoryInterface
{
    public function existsActiveMembership(
        int|string $userId,
        int|string $tenantId,
    ): bool;
}
