<?php

declare(strict_types=1);

namespace Tenancy\Tests\Fixtures;

use Tenancy\Contracts\Records\TenantApiKeyRecordInterface;
use Tenancy\Contracts\Repositories\TenantApiKeyLookupInterface;

final class FakeTenantApiKeyLookup implements TenantApiKeyLookupInterface
{
    public ?TenantApiKeyRecordInterface $record = null;

    public function findByPlainTextKey(string $plainTextKey): ?TenantApiKeyRecordInterface
    {
        return $this->record;
    }
}
