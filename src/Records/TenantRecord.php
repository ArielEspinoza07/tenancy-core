<?php

declare(strict_types=1);

namespace Tenancy\Records;

use Tenancy\Contracts\Records\TenantRecordInterface;
use Tenancy\Enums\TenantStatus;

final readonly class TenantRecord implements TenantRecordInterface
{
    public string $status;

    /**
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        public int|string $id,
        public string $name,
        public string $slug,
        public ?string $domain,
        public array $metadata,
        private TenantStatus $tenantStatus,
    ) {
        $this->status = $this->tenantStatus->value;
    }

    public function isActive(): bool
    {
        return $this->tenantStatus->isActive();
    }

    public function isSuspended(): bool
    {
        return $this->tenantStatus->isSuspended();
    }

    public function isDeleted(): bool
    {
        return $this->tenantStatus->isDeleted();
    }

    public function isPending(): bool
    {
        return $this->tenantStatus->isPending();
    }
}
