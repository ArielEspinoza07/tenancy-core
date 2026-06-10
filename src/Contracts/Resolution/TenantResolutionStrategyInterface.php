<?php

declare(strict_types=1);

namespace Tenancy\Contracts\Resolution;

use Tenancy\Contracts\Context\TenantContextInterface;

interface TenantResolutionStrategyInterface
{
    public function supports(TenantResolutionInputInterface $input): bool;

    public function resolve(TenantResolutionInputInterface $input): ?TenantContextInterface;
}
