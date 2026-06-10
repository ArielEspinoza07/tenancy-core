<?php

declare(strict_types=1);

namespace Tenancy\Authorization;

use Tenancy\Contracts\Authorization\TenantPermissionCheckerInterface;
use Tenancy\Contracts\Context\TenantContextInterface;
use Tenancy\Contracts\Repositories\TenantPermissionRepositoryInterface;
use Tenancy\Exceptions\TenantPermissionDeniedException;

final readonly class TenantPermissionChecker implements TenantPermissionCheckerInterface
{
    public function __construct(private TenantPermissionRepositoryInterface $repository) {}

    public function ensureCan(int|string $userId, TenantContextInterface $context, string $permission): void
    {
        if (! $this->can($userId, $context, $permission)) {
            throw new TenantPermissionDeniedException(
                sprintf(
                    'User "%s" in tenant "%s" does not have the permission "%s".',
                    $userId,
                    $context->record->slug,
                    $permission,
                ),
            );
        }
    }

    public function can(int|string $userId, TenantContextInterface $context, string $permission): bool
    {
        return $this->repository->userHasPermission(
            tenantId: $context->record->id,
            userId: $userId,
            permission: $permission,
        );
    }
}
