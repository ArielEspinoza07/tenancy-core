<?php

declare(strict_types=1);

use Tenancy\Context\TenantContext;
use Tenancy\Enums\TenantResolutionSource;

it('reports isTenant() true and isSystem() false for non-system sources', function (TenantResolutionSource $source) {
    $context = new TenantContext(makeTenantRecord(), $source);

    expect($context->isTenant())->toBeTrue()
        ->and($context->isSystem())->toBeFalse();
})->with([
    'subdomain'     => TenantResolutionSource::Subdomain,
    'custom_domain' => TenantResolutionSource::CustomDomain,
    'header'        => TenantResolutionSource::Header,
    'path'          => TenantResolutionSource::Path,
    'session'       => TenantResolutionSource::Session,
    'api_key'       => TenantResolutionSource::ApiKey,
]);

it('reports isSystem() true and isTenant() false for system source', function () {
    $context = new TenantContext(makeTenantRecord(), TenantResolutionSource::System);

    expect($context->isSystem())->toBeTrue()
        ->and($context->isTenant())->toBeFalse();
});

it('exposes the tenant record and source', function () {
    $record = makeTenantRecord();
    $context = new TenantContext($record, TenantResolutionSource::Subdomain);

    expect($context->record)->toBe($record)
        ->and($context->source)->toBe(TenantResolutionSource::Subdomain);
});
