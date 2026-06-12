<?php

declare(strict_types=1);

namespace Tenancy\Context;

use Closure;
use Tenancy\Contracts\Context\CurrentTenantInterface;
use Tenancy\Contracts\Context\TenantContextInterface;
use Tenancy\Exceptions\TenantNotResolvedException;

final class CurrentTenant implements CurrentTenantInterface
{
    private ?TenantContextInterface $context = null;

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

    public function scoped(TenantContextInterface $context, Closure $callback): mixed
    {
        $previous = $this->context;

        try {
            $this->set($context);

            return $callback();
        } finally {
            $this->context = $previous;
        }
    }

    public function withoutTenant(Closure $callback): mixed
    {
        $previous = $this->context;

        try {
            $this->clear();

            return $callback();
        } finally {
            $this->context = $previous;
        }
    }
}
