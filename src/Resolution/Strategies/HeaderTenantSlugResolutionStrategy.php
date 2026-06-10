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

final readonly class HeaderTenantSlugResolutionStrategy implements TenantResolutionStrategyInterface
{
    public function __construct(
        private TenantLookupInterface $repository,
        private string $headerName = 'X-Tenant-Slug',
    ) {}
    public function supports(TenantResolutionInputInterface $input): bool
    {
        return $this->slug($input) !== null;
    }

    public function resolve(TenantResolutionInputInterface $input): ?TenantContextInterface
    {
        $slug = $this->slug($input);

        if ($slug === null) {
            return null;
        }

        $record = $this->repository->findBySlug($slug);
        if ($record === null) {
            throw new TenantNotFoundException(
                sprintf('Tenant "%s" not found.', $slug),
            );
        }

        if ($record->isSuspended()) {
            throw new TenantSuspendedException(
                sprintf('Tenant "%s" is suspended.', $record->id),
            );
        }

        if (! $record->isActive()) {
            throw new TenantNotFoundException(
                sprintf('Tenant "%s" not found.', $record->slug),
            );
        }

        return new TenantContext(
            record: $record,
            source: TenantResolutionSource::Header,
        );
    }

    private function slug(TenantResolutionInputInterface $input): ?string
    {
        $value = $input->header($this->headerName);

        if ($value === null) {
            return null;
        }

        $value = mb_strtolower(mb_trim($value));

        if ($value === '') {
            return null;
        }

        if (! preg_match('/^[a-z0-9][a-z0-9-]*[a-z0-9]$|^[a-z0-9]$/', $value)) {
            return null;
        }

        return $value;
    }
}
