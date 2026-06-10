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

final readonly class HeaderTenantResolutionStrategy implements TenantResolutionStrategyInterface
{
    public function __construct(
        private TenantLookupInterface $repository,
        private string $headerName = 'X-Tenant-ID',
    ) {}

    public function supports(TenantResolutionInputInterface $input): bool
    {
        return $this->tenantIdentifier($input) !== null;
    }

    public function resolve(TenantResolutionInputInterface $input): ?TenantContextInterface
    {
        $tenantId = $this->tenantIdentifier($input);

        if ($tenantId === null) {
            return null;
        }

        $record = $this->repository->findById($tenantId);
        if ($record === null) {
            throw new TenantNotFoundException(
                sprintf('Tenant "%s" not found.', $tenantId),
            );
        }

        if ($record->isSuspended()) {
            throw new TenantSuspendedException(
                sprintf('Tenant "%s" is suspended.', $record->id),
            );
        }

        if (! $record->isActive()) {
            throw new TenantNotFoundException(
                sprintf('Tenant "%s" not found.', $record->id),
            );
        }

        return new TenantContext(
            record: $record,
            source: TenantResolutionSource::Header,
        );
    }

    private function tenantIdentifier(TenantResolutionInputInterface $input): int|string|null
    {
        $value = $input->header($this->headerName);

        if ($value === null) {
            return null;
        }

        $value = mb_trim($value);

        if ($value === '') {
            return null;
        }

        if (ctype_digit($value)) {
            return (int) $value;
        }

        return $value;
    }
}
