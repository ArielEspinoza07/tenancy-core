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

it('scoped() sets the context for the duration of the callback', function () {
    $current = new CurrentTenant();
    $context = makeTenantContext();

    $current->scoped($context, function () use ($current, $context) {
        expect($current->get())->toBe($context);
    });
});

it('scoped() restores the previous context after the callback', function () {
    $current = new CurrentTenant();
    $outer = makeTenantContext();
    $inner = makeTenantContext();
    $current->set($outer);

    $current->scoped($inner, fn () => null);

    expect($current->get())->toBe($outer);
});

it('scoped() restores to no context when none was set before', function () {
    $current = new CurrentTenant();

    $current->scoped(makeTenantContext(), fn () => null);

    expect($current->has())->toBeFalse();
});

it('scoped() restores context even when the callback throws', function () {
    $current = new CurrentTenant();
    $outer = makeTenantContext();
    $current->set($outer);

    expect(fn () => $current->scoped(makeTenantContext(), fn () => throw new RuntimeException()))
        ->toThrow(RuntimeException::class)
        ->and($current->get())->toBe($outer);
});

it('scoped() returns the callback return value', function () {
    $current = new CurrentTenant();

    $result = $current->scoped(makeTenantContext(), fn () => 42);

    expect($result)->toBe(42);
});

it('scoped() supports nested calls', function () {
    $current = new CurrentTenant();
    $outer = makeTenantContext();
    $inner = makeTenantContext();
    $current->set($outer);

    $current->scoped($inner, function () use ($current, $outer, $inner) {
        expect($current->get())->toBe($inner);

        $current->scoped($outer, function () use ($current, $outer) {
            expect($current->get())->toBe($outer);
        });

        expect($current->get())->toBe($inner);
    });

    expect($current->get())->toBe($outer);
});

it('withoutContext() clears the context for the duration of the callback', function () {
    $current = new CurrentTenant();
    $current->set(makeTenantContext());

    $current->withoutTenant(function () use ($current) {
        expect($current->has())->toBeFalse();
    });
});

it('withoutContext() restores the previous context after the callback', function () {
    $current = new CurrentTenant();
    $context = makeTenantContext();
    $current->set($context);

    $current->withoutTenant(fn () => null);

    expect($current->get())->toBe($context);
});

it('withoutContext() stays clear when no context was set before', function () {
    $current = new CurrentTenant();

    $current->withoutTenant(fn () => null);

    expect($current->has())->toBeFalse();
});

it('withoutContext() restores context even when the callback throws', function () {
    $current = new CurrentTenant();
    $context = makeTenantContext();
    $current->set($context);

    expect(fn () => $current->withoutTenant(fn () => throw new RuntimeException()))
        ->toThrow(RuntimeException::class)
        ->and($current->get())->toBe($context);
});

it('withoutContext() returns the callback return value', function () {
    $current = new CurrentTenant();

    $result = $current->withoutTenant(fn () => 42);

    expect($result)->toBe(42);
});
