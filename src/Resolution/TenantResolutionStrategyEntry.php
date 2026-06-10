<?php

declare(strict_types=1);

namespace Tenancy\Resolution;

use Tenancy\Contracts\Resolution\TenantResolutionStrategyInterface;

final readonly class TenantResolutionStrategyEntry
{
    public function __construct(
        public TenantResolutionStrategyInterface $strategy,
        public int $priority = 0,
    ) {}
}
