<?php

declare(strict_types=1);

namespace Tenancy\Tests\Fixtures;

use Tenancy\Contracts\Repositories\TenantPermissionRepositoryInterface;

final class FakeTenantPermissionRepository implements TenantPermissionRepositoryInterface
{
    public bool $hasPermission = false;

    public function userHasPermission(int|string $tenantId, int|string $userId, string $permission): bool
    {
        return $this->hasPermission;
    }
}
