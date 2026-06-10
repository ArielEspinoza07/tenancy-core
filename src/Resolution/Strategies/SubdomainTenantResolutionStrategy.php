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

final readonly class SubdomainTenantResolutionStrategy implements TenantResolutionStrategyInterface
{
    /**
     * @param list<string> $reservedSubdomains
     */
    public function __construct(
        private TenantLookupInterface $repository,
        private HostNormalizerInterface $hostNormalizer,
        private string $baseDomain,
        private array $reservedSubdomains = ['www', 'app', 'admin', 'api'],
    ) {}
    public function supports(TenantResolutionInputInterface $input): bool
    {
        $host = $this->hostNormalizer->normalize($input->host);

        if ($host === null) {
            return false;
        }

        return $this->extractSubdomain($host) !== null;
    }

    public function resolve(TenantResolutionInputInterface $input): ?TenantContextInterface
    {
        $host = $this->hostNormalizer->normalize($input->host);

        if ($host === null) {
            return null;
        }

        $slug = $this->extractSubdomain($host);

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
            source: TenantResolutionSource::Subdomain,
        );
    }

    private function extractSubdomain(string $host): ?string
    {
        $baseDomain = $this->hostNormalizer->normalize($this->baseDomain);

        if ($host === $baseDomain) {
            return null;
        }

        $suffix = '.' . $baseDomain;

        if (! str_ends_with($host, $suffix)) {
            return null;
        }

        $subdomain = mb_substr($host, 0, -mb_strlen($suffix));

        if ($subdomain === '') {
            return null;
        }

        if (str_contains($subdomain, '.')) {
            return null;
        }

        if (in_array($subdomain, $this->reservedSubdomains, true)) {
            return null;
        }

        return $subdomain;
    }
}
