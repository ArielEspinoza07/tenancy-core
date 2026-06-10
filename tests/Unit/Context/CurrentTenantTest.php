<?php

declare(strict_types=1);

use Tenancy\Context\CurrentTenant;
use Tenancy\Exceptions\TenantNotResolvedException;

it('has() returns false when no tenant is set', function () {
    expect(new CurrentTenant()->has())->toBeFalse();
});

it('has() returns true after set()', function () {
    $current = new CurrentTenant();

    $current->set(makeTenantContext());

    expect($current->has())->toBeTrue();
});

it('get() returns the context that was set', function () {
    $current = new CurrentTenant();
    $context = makeTenantContext();
    $current->set($context);

    expect($current->get())->toBe($context);
});

it('set() replaces the previous context', function () {
    $current = new CurrentTenant();
    $first = makeTenantContext();
    $second = makeTenantContext();

    $current->set($first);
    $current->set($second);

    expect($current->get())->toBe($second);
});

it('clear() removes the tenant context', function () {
    $current = new CurrentTenant();
    $current->set(makeTenantContext());
    $current->clear();

    expect($current->has())->toBeFalse();
});

it('get() throws TenantNotResolvedException when no tenant is set', function () {
    expect(fn () => new CurrentTenant()->get())
        ->toThrow(TenantNotResolvedException::class);
});

it('get() throws TenantNotResolvedException after clear()', function () {
    $current = new CurrentTenant();
    $current->set(makeTenantContext());
    $current->clear();

    expect(fn () => $current->get())
        ->toThrow(TenantNotResolvedException::class);
});
