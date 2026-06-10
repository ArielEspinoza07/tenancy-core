<?php

declare(strict_types=1);

use Tenancy\Access\TenantAccessGuard;
use Tenancy\Enums\TenantResolutionSource;
use Tenancy\Exceptions\TenantAccessDeniedException;
use Tenancy\Tests\Fixtures\FakeMembershipRepository;

beforeEach(function () {
    $this->repository = new FakeMembershipRepository();
    $this->guard = new TenantAccessGuard($this->repository);
    $this->tenantContext = makeTenantContext();
    $this->systemTenantContext = makeTenantContext(TenantResolutionSource::System);
});

// --- canAccess() ---

it('canAccess() returns true when the user has an active membership', function () {
    $this->repository->hasMembership = true;

    expect($this->guard->canAccess(userId: 1, context: $this->tenantContext))->toBeTrue();
});

it('canAccess() returns false when the user has no active membership', function () {
    expect($this->guard->canAccess(userId: 1, context: $this->tenantContext))->toBeFalse();
});

it('canAccess() returns true for a system context regardless of membership', function () {
    expect($this->guard->canAccess(userId: 1, context: $this->systemTenantContext))->toBeTrue();
});

it('canAccess() accepts a string userId', function () {
    $this->repository->hasMembership = true;

    expect($this->guard->canAccess(userId: 'uuid-99', context: $this->tenantContext))->toBeTrue();
});

// --- ensureAccess() ---

it('ensureAccess() does not throw when the user has access', function () {
    $this->repository->hasMembership = true;

    expect(fn () => $this->guard->ensureAccess(userId: 1, context: $this->tenantContext))
        ->not->toThrow(TenantAccessDeniedException::class);
});

it('ensureAccess() throws TenantAccessDeniedException when the user has no membership', function () {
    expect(fn () => $this->guard->ensureAccess(userId: 1, context: $this->tenantContext))
        ->toThrow(TenantAccessDeniedException::class);
});

it('ensureAccess() exception message contains the userId and tenant slug', function () {
    expect(fn () => $this->guard->ensureAccess(userId: 42, context: $this->tenantContext))
        ->toThrow(TenantAccessDeniedException::class, '42')
        ->toThrow(TenantAccessDeniedException::class, $this->tenantContext->record->slug);
});

it('ensureAccess() does not throw for a system context even without membership', function () {
    expect(fn () => $this->guard->ensureAccess(userId: 1, context: $this->systemTenantContext))
        ->not->toThrow(TenantAccessDeniedException::class);
});
