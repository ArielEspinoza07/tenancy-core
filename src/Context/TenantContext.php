<?php

declare(strict_types=1);

namespace Tenancy\Context;

use Tenancy\Contracts\Context\TenantContextInterface;
use Tenancy\Contracts\Records\TenantRecordInterface;
use Tenancy\Enums\TenantResolutionSource;

final readonly class TenantContext implements TenantContextInterface
{
    public function __construct(
        public TenantRecordInterface $record,
        public TenantResolutionSource $source,
    ) {}

    public function isTenant(): bool
    {
        return $this->source->isTenant();
    }

    public function isSystem(): bool
    {
        return $this->source->isSystem();
    }
}
