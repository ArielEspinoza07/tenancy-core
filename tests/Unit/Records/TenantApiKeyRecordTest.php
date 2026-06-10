<?php

declare(strict_types=1);

use Tenancy\Records\TenantApiKeyRecord;

// --- isExpired() ---

it('isExpired() returns false when expiresAt is null', function () {
    $key = new TenantApiKeyRecord(tenant: makeTenantRecord(), revoked: false, expiresAt: null);

    expect($key->isExpired())->toBeFalse();
});

it('isExpired() returns false when expiresAt is in the future', function () {
    $key = new TenantApiKeyRecord(
        tenant: makeTenantRecord(),
        revoked: false,
        expiresAt: new DateTimeImmutable('+1 hour'),
    );

    expect($key->isExpired())->toBeFalse();
});

it('isExpired() returns true when expiresAt is in the past', function () {
    $key = new TenantApiKeyRecord(
        tenant: makeTenantRecord(),
        revoked: false,
        expiresAt: new DateTimeImmutable('-1 second'),
    );

    expect($key->isExpired())->toBeTrue();
});

// --- isActive() ---

it('isActive() returns true when not revoked and no expiry', function () {
    $key = new TenantApiKeyRecord(tenant: makeTenantRecord(), revoked: false, expiresAt: null);

    expect($key->isActive())->toBeTrue();
});

it('isActive() returns true when not revoked and expiry is in the future', function () {
    $key = new TenantApiKeyRecord(
        tenant: makeTenantRecord(),
        revoked: false,
        expiresAt: new DateTimeImmutable('+1 day'),
    );

    expect($key->isActive())->toBeTrue();
});

it('isActive() returns false when revoked', function () {
    $key = new TenantApiKeyRecord(tenant: makeTenantRecord(), revoked: true, expiresAt: null);

    expect($key->isActive())->toBeFalse();
});

it('isActive() returns false when expired', function () {
    $key = new TenantApiKeyRecord(
        tenant: makeTenantRecord(),
        revoked: false,
        expiresAt: new DateTimeImmutable('-1 second'),
    );

    expect($key->isActive())->toBeFalse();
});

it('isActive() returns false when both revoked and expired', function () {
    $key = new TenantApiKeyRecord(
        tenant: makeTenantRecord(),
        revoked: true,
        expiresAt: new DateTimeImmutable('-1 hour'),
    );

    expect($key->isActive())->toBeFalse();
});

it('exposes the tenant record', function () {
    $tenant = makeTenantRecord();
    $key = new TenantApiKeyRecord(tenant: $tenant, revoked: false);

    expect($key->tenant)->toBe($tenant);
});
