<?php

declare(strict_types=1);

namespace Tenancy\Contracts\Context;

use Tenancy\Contracts\Records\TenantRecordInterface;
use Tenancy\Enums\TenantResolutionSource;

interface TenantContextInterface
{
    public TenantRecordInterface $record { get; }

    public TenantResolutionSource $source { get; }

    public function isTenant(): bool;

    public function isSystem(): bool;
}
