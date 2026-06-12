<?php

declare(strict_types=1);

namespace Tenancy\Contracts\Context;

use Closure;

interface CurrentTenantInterface
{
    public function set(TenantContextInterface $context): void;

    public function get(): TenantContextInterface;

    public function has(): bool;

    public function clear(): void;

    /**
     * @param  Closure(): mixed  $callback
     */
    public function scoped(TenantContextInterface $context, Closure $callback): mixed;

    /**
     * @param  Closure(): mixed  $callback
     */
    public function withoutTenant(Closure $callback): mixed;
}
