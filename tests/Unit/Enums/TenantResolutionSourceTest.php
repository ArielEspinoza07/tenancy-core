<?php

declare(strict_types=1);

use Tenancy\Enums\TenantResolutionSource;

// --- Backing values ---

it('has the expected string backing values', function (TenantResolutionSource $source, string $value) {
    expect($source->value)->toBe($value);
})->with([
    [TenantResolutionSource::ApiKey,       'api_key'],
    [TenantResolutionSource::CustomDomain, 'custom_domain'],
    [TenantResolutionSource::Header,       'header'],
    [TenantResolutionSource::Path,         'path'],
    [TenantResolutionSource::Session,      'session'],
    [TenantResolutionSource::Subdomain,    'subdomain'],
    [TenantResolutionSource::System,       'system'],
]);

it('can be created from its string value', function (TenantResolutionSource $source) {
    expect(TenantResolutionSource::from($source->value))->toBe($source);
})->with(TenantResolutionSource::cases());

// --- isSystem() ---

it('isSystem() returns true only for System', function () {
    expect(TenantResolutionSource::System->isSystem())->toBeTrue();
});

it('isSystem() returns false for all non-system sources', function (TenantResolutionSource $source) {
    expect($source->isSystem())->toBeFalse();
})->with([
    TenantResolutionSource::ApiKey,
    TenantResolutionSource::CustomDomain,
    TenantResolutionSource::Header,
    TenantResolutionSource::Path,
    TenantResolutionSource::Session,
    TenantResolutionSource::Subdomain,
]);
