<?php

declare(strict_types=1);

namespace Tenancy\Contracts\Repositories;

use Tenancy\Contracts\Records\TenantApiKeyRecordInterface;

interface TenantApiKeyLookupInterface
{
    public function findByPlainTextKey(string $plainTextKey): ?TenantApiKeyRecordInterface;
}
