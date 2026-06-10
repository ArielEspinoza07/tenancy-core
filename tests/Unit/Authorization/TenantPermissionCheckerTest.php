<?php

declare(strict_types=1);

use Tenancy\Authorization\TenantPermissionChecker;
use Tenancy\Exceptions\TenantPermissionDeniedException;
use Tenancy\Tests\Fixtures\FakeTenantPermissionRepository;

beforeEach(function () {
    $this->repository = new FakeTenantPermissionRepository();
    $this->checker = new TenantPermissionChecker($this->repository);
    $this->tenantContext = makeTenantContext();
});

// --- can() ---

it('can() returns true when the user has the permission', function () {
    $this->repository->hasPermission = true;

    expect($this->checker->can(userId: 1, context: $this->tenantContext, permission: 'posts.create'))->toBeTrue();
});

it('can() returns false when the user lacks the permission', function () {
    expect($this->checker->can(userId: 1, context: $this->tenantContext, permission: 'posts.create'))->toBeFalse();
});

it('can() accepts a string userId', function () {
    $this->repository->hasPermission = true;

    expect($this->checker->can(userId: 'uuid-99', context: $this->tenantContext, permission: 'posts.create'))->toBeTrue();
});

// --- ensureCan() ---

it('ensureCan() does not throw when the user has the permission', function () {
    $this->repository->hasPermission = true;

    expect(fn () => $this->checker->ensureCan(userId: 1, context: $this->tenantContext, permission: 'posts.create'))
        ->not->toThrow(TenantPermissionDeniedException::class);
});

it('ensureCan() throws TenantPermissionDeniedException when the user lacks the permission', function () {
    expect(fn () => $this->checker->ensureCan(userId: 1, context: $this->tenantContext, permission: 'posts.create'))
        ->toThrow(TenantPermissionDeniedException::class);
});

it('ensureCan() exception message contains the userId, tenant slug, and permission', function () {
    expect(fn () => $this->checker->ensureCan(userId: 42, context: $this->tenantContext, permission: 'posts.delete'))
        ->toThrow(TenantPermissionDeniedException::class, '42')
        ->toThrow(TenantPermissionDeniedException::class, $this->tenantContext->record->slug)
        ->toThrow(TenantPermissionDeniedException::class, 'posts.delete');
});
