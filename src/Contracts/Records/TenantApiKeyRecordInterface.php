<?php

declare(strict_types=1);

namespace Tenancy\Contracts\Records;

use DateTimeImmutable;

interface TenantApiKeyRecordInterface
{
    public TenantRecordInterface $tenant { get; }

    public bool $revoked { get; }

    public ?DateTimeImmutable $expiresAt { get; }

    public function isActive(): bool;

    public function isExpired(): bool;
}
