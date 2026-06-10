<?php

declare(strict_types=1);

namespace Tenancy\Resolution\Strategies;

use Tenancy\Context\TenantContext;
use Tenancy\Contracts\Context\TenantContextInterface;
use Tenancy\Contracts\Repositories\TenantApiKeyLookupInterface;
use Tenancy\Contracts\Resolution\TenantResolutionInputInterface;
use Tenancy\Contracts\Resolution\TenantResolutionStrategyInterface;
use Tenancy\Enums\TenantResolutionSource;
use Tenancy\Exceptions\TenantNotFoundException;
use Tenancy\Exceptions\TenantSuspendedException;

final readonly class ApiKeyTenantResolutionStrategy implements TenantResolutionStrategyInterface
{
    public function __construct(
        private TenantApiKeyLookupInterface $repository,
    ) {}

    public function supports(TenantResolutionInputInterface $input): bool
    {
        return $this->apiKey($input) !== null;
    }

    public function resolve(TenantResolutionInputInterface $input): ?TenantContextInterface
    {
        $plainTextKey = $this->apiKey($input);

        if ($plainTextKey === null) {
            return null;
        }

        $apiKeyRecord = $this->repository->findByPlainTextKey($plainTextKey);

        if ($apiKeyRecord === null || ! $apiKeyRecord->isActive()) {
            throw new TenantNotFoundException('Tenant not found.');
        }

        if ($apiKeyRecord->tenant->isSuspended()) {
            throw new TenantSuspendedException(
                sprintf('Tenant %s is suspended.', $apiKeyRecord->tenant->id)
            );
        }

        if (! $apiKeyRecord->tenant->isActive()) {
            throw new TenantNotFoundException(
                sprintf('Tenant %s not found.', $apiKeyRecord->tenant->id)
            );
        }

        return new TenantContext(
            record: $apiKeyRecord->tenant,
            source: TenantResolutionSource::ApiKey,
        );
    }

    private function apiKey(TenantResolutionInputInterface $input): ?string
    {
        $apiKey = $input->apiKey;

        if ($apiKey === null) {
            return null;
        }

        $apiKey = mb_trim($apiKey);

        return $apiKey !== '' ? $apiKey : null;
    }
}
