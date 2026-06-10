<?php

declare(strict_types=1);

namespace Tenancy\Resolution\Strategies;

use Tenancy\Context\TenantContext;
use Tenancy\Contracts\Context\TenantContextInterface;
use Tenancy\Contracts\Repositories\TenantLookupInterface;
use Tenancy\Contracts\Resolution\TenantResolutionInputInterface;
use Tenancy\Contracts\Resolution\TenantResolutionStrategyInterface;
use Tenancy\Enums\TenantResolutionSource;
use Tenancy\Exceptions\TenantNotFoundException;
use Tenancy\Exceptions\TenantSuspendedException;

final readonly class SessionTenantResolutionStrategy implements TenantResolutionStrategyInterface
{
    public function __construct(
        private TenantLookupInterface $repository,
    ) {}
    public function supports(TenantResolutionInputInterface $input): bool
    {
        return $this->tenantId($input) !== null;
    }

    public function resolve(TenantResolutionInputInterface $input): ?TenantContextInterface
    {
        $tenantId = $this->tenantId($input);

        if ($tenantId === null) {
            return null;
        }

        $record = $this->repository->findById($tenantId);
        if ($record === null) {
            throw new TenantNotFoundException(
                sprintf('Tenant %s not found', $tenantId),
            );
        }

        if ($record->isSuspended()) {
            throw new TenantSuspendedException(
                sprintf('Tenant %s is suspended.', $record->id),
            );
        }

        if (! $record->isActive()) {
            throw new TenantNotFoundException(
                sprintf('Tenant "%s" not found.', $record->id),
            );
        }

        return new TenantContext(
            record: $record,
            source: TenantResolutionSource::Session,
        );
    }

    private function tenantId(TenantResolutionInputInterface $input): int|string|null
    {
        $tenantId = $input->sessionTenantId;

        if ($tenantId === null) {
            return null;
        }

        if (is_int($tenantId)) {
            return $tenantId > 0 ? $tenantId : null;
        }

        $tenantId = mb_trim($tenantId);

        if ($tenantId === '') {
            return null;
        }

        if (ctype_digit($tenantId)) {
            return (int) $tenantId;
        }

        return $tenantId;
    }
}
