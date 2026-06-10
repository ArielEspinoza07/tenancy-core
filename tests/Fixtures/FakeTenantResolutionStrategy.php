<?php

declare(strict_types=1);

namespace Tenancy\Tests\Fixtures;

use Tenancy\Contracts\Context\TenantContextInterface;
use Tenancy\Contracts\Resolution\TenantResolutionInputInterface;
use Tenancy\Contracts\Resolution\TenantResolutionStrategyInterface;

final class FakeTenantResolutionStrategy implements TenantResolutionStrategyInterface
{
    public bool $supports = true;

    public ?TenantContextInterface $context = null;

    public function supports(TenantResolutionInputInterface $input): bool
    {
        return $this->supports;
    }

    public function resolve(TenantResolutionInputInterface $input): ?TenantContextInterface
    {
        return $this->context;
    }
}
