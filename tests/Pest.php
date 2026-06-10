<?php

declare(strict_types=1);

use Tenancy\Context\TenantContext;
use Tenancy\Enums\TenantResolutionSource;
use Tenancy\Enums\TenantStatus;
use Tenancy\Records\TenantRecord;

function makeTenantRecord(
    TenantStatus $status = TenantStatus::Active,
    int|string $id = 1,
    string $name = 'Acme',
    string $slug = 'acme',
    ?string $domain = null,
    array $metadata = [],
): TenantRecord {
    return new TenantRecord(
        id: $id,
        name: $name,
        slug: $slug,
        domain: $domain,
        metadata: $metadata,
        tenantStatus: $status,
    );
}

function makeTenantContext(
    TenantResolutionSource $source = TenantResolutionSource::Subdomain,
): TenantContext {
    return new TenantContext(
        record: makeTenantRecord(),
        source: $source,
    );
}
