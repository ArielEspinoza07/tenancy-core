<?php

declare(strict_types=1);

namespace Tenancy\Contracts\Repositories;

use Tenancy\Contracts\Records\TenantRecordInterface;

interface TenantLookupInterface
{
    public function findBySlug(string $slug): ?TenantRecordInterface;

    public function findByDomain(string $domain): ?TenantRecordInterface;

    public function findById(int|string $id): ?TenantRecordInterface;
}
