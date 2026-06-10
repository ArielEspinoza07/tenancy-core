<?php

declare(strict_types=1);

namespace Tenancy\Context;

use Tenancy\Contracts\Context\CurrentTenantInterface;
use Tenancy\Contracts\Context\TenantContextInterface;
use Tenancy\Exceptions\TenantNotResolvedException;

final class CurrentTenant implements CurrentTenantInterface
{
    private ?TenantContextInterface $context = null;
    public function __construct() {}

    public function set(TenantContextInterface $context): void
    {
        $this->context = $context;
    }

    public function get(): TenantContextInterface
    {
        if ($this->context === null) {
            throw new TenantNotResolvedException('No tenant context has been set.');
        }

        return $this->context;
    }

    public function has(): bool
    {
        return $this->context !== null;
    }

    public function clear(): void
    {
        $this->context = null;
    }
}
