<?php

declare(strict_types=1);

namespace Tenancy\Resolution\Strategies;

use Tenancy\Context\TenantContext;
use Tenancy\Contracts\Context\TenantContextInterface;
use Tenancy\Contracts\Repositories\TenantLookupInterface;
use Tenancy\Contracts\Resolution\TenantResolutionInputInterface;
use Tenancy\Contracts\Resolution\TenantResolutionStrategyInterface;
use Tenancy\Contracts\Support\HostNormalizerInterface;
use Tenancy\Enums\TenantResolutionSource;
use Tenancy\Exceptions\TenantNotFoundException;
use Tenancy\Exceptions\TenantSuspendedException;

final readonly class CustomDomainTenantResolutionStrategy implements TenantResolutionStrategyInterface
{
    /**
     * @param list<non-empty-string> $platformDomains
     */
    public function __construct(
        private TenantLookupInterface $repository,
        private HostNormalizerInterface $hostNormalizer,
        private array $platformDomains = [],
        private bool $throwWhenUnregistered = true,
    ) {}

    public function supports(TenantResolutionInputInterface $input): bool
    {
        $host = $this->hostNormalizer->normalize($input->host);

        if ($host === null) {
            return false;
        }

        return ! $this->isPlatformDomain($host);
    }

    public function resolve(TenantResolutionInputInterface $input): ?TenantContextInterface
    {
        $host = $this->hostNormalizer->normalize($input->host);

        if ($host === null) {
            return null;
        }

        if ($this->isPlatformDomain($host)) {
            return null;
        }

        $record = $this->repository->findByDomain($host);

        if ($record === null) {
            if ($this->throwWhenUnregistered) {
                throw new TenantNotFoundException(
                    sprintf('Tenant %s not found.', $host),
                );
            }

            return null;
        }

        if ($record->isSuspended()) {
            throw new TenantSuspendedException(
                sprintf('Tenant %s is suspended.', $record->id),
            );
        }

        if (! $record->isActive()) {
            throw new TenantNotFoundException(
                sprintf('Tenant "%s" not found.', $record->slug),
            );
        }

        return new TenantContext(
            record: $record,
            source: TenantResolutionSource::CustomDomain,
        );
    }

    private function isPlatformDomain(string $host): bool
    {
        foreach ($this->platformDomains as $platformDomain) {
            $platformDomain = $this->hostNormalizer->normalize($platformDomain);

            if ($platformDomain === null) {
                continue;
            }

            if ($host === $platformDomain) {
                return true;
            }

            if (str_ends_with($host, '.' . $platformDomain)) {
                return true;
            }
        }

        return false;
    }
}
