<?php

declare(strict_types=1);

namespace Tenancy\Contracts\Repositories;

interface TenantPermissionRepositoryInterface
{
    public function userHasPermission(
        int|string $tenantId,
        int|string $userId,
        string $permission,
    ): bool;
}
