<?php

declare (strict_types=1);

namespace Tenancy\Access;

use Tenancy\Contracts\Access\TenantAccessGuardInterface;
use Tenancy\Contracts\Context\TenantContextInterface;
use Tenancy\Contracts\Repositories\MembershipRepositoryInterface;
use Tenancy\Exceptions\TenantAccessDeniedException;

final readonly class TenantAccessGuard implements TenantAccessGuardInterface
{
    public function __construct(private MembershipRepositoryInterface $repository) {}

    public function ensureAccess(int|string $userId, TenantContextInterface $context): void
    {
        if (! $this->canAccess($userId, $context)) {
            throw new TenantAccessDeniedException(
                sprintf('User "%s" does not have access to tenant "%s"', $userId, $context->record->slug),
            );
        }
    }

    public function canAccess(int|string $userId, TenantContextInterface $context): bool
    {
        if ($context->isSystem()) {
            return true;
        }

        return $this->repository->existsActiveMembership(
            userId: $userId,
            tenantId: $context->record->id,
        );
    }
}
