<?php

declare(strict_types=1);

namespace Tenancy\Contracts\Context;

interface CurrentTenantInterface
{
    public function set(TenantContextInterface $context): void;

    public function get(): TenantContextInterface;

    public function has(): bool;

    public function clear(): void;
}
