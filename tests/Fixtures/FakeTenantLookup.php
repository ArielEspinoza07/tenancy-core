<?php

declare(strict_types=1);

namespace Tenancy\Tests\Fixtures;

use Tenancy\Contracts\Records\TenantRecordInterface;
use Tenancy\Contracts\Repositories\TenantLookupInterface;

final class FakeTenantLookup implements TenantLookupInterface
{
    public ?TenantRecordInterface $record = null;

    public function findBySlug(string $slug): ?TenantRecordInterface
    {
        return $this->record;
    }

    public function findByDomain(string $domain): ?TenantRecordInterface
    {
        return $this->record;
    }

    public function findById(int|string $id): ?TenantRecordInterface
    {
        return $this->record;
    }
}
