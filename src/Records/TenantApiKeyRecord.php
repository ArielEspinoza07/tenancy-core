<?php

declare(strict_types=1);

namespace Tenancy\Records;

use DateTimeImmutable;
use Tenancy\Contracts\Records\TenantApiKeyRecordInterface;
use Tenancy\Contracts\Records\TenantRecordInterface;

final readonly class TenantApiKeyRecord implements TenantApiKeyRecordInterface
{
    public function __construct(
        public TenantRecordInterface $tenant,
        public bool $revoked,
        public ?DateTimeImmutable $expiresAt = null,
    ) {}

    public function isActive(): bool
    {
        return ! $this->revoked && ! $this->isExpired();
    }

    public function isExpired(): bool
    {
        return $this->expiresAt !== null
            && $this->expiresAt <= new DateTimeImmutable;
    }
}
