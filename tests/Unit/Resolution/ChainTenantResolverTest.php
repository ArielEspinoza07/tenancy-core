<?php

declare(strict_types=1);

use Tenancy\Context\TenantContext;
use Tenancy\Enums\TenantResolutionSource;
use Tenancy\Exceptions\TenantResolutionConflictException;
use Tenancy\Resolution\ChainTenantResolver;
use Tenancy\Resolution\TenantResolutionInput;
use Tenancy\Resolution\TenantResolverRegistry;
use Tenancy\Tests\Fixtures\FakeTenantResolutionStrategy;

function makeResolver(
    TenantResolverRegistry $registry,
): ChainTenantResolver {
    return new ChainTenantResolver($registry);
}

beforeEach(function () {
    $this->tenantResolutionInput = TenantResolutionInput::fromArray([]);
    $this->registry = new TenantResolverRegistry();
});

it('resolves to the context returned by a supporting strategy', function () {
    $context = makeTenantContext();
    $strategy = new FakeTenantResolutionStrategy();
    $strategy->context = $context;
    $this->registry->add($strategy);

    $resolved = makeResolver($this->registry)->resolve($this->tenantResolutionInput);

    expect($resolved)->toBe($context);
});

it('skips strategies that do not support the input', function () {
    $context = makeTenantContext();

    $unsupported = new FakeTenantResolutionStrategy();
    $unsupported->supports = false;
    $this->registry->add($unsupported);

    $supported = new FakeTenantResolutionStrategy();
    $supported->context = $context;
    $this->registry->add($supported);

    $resolved = makeResolver($this->registry)->resolve($this->tenantResolutionInput);

    expect($resolved)->toBe($context);
});

it('skips strategies that support but return null', function () {
    $context = makeTenantContext();

    $returnsNull = new FakeTenantResolutionStrategy();
    $returnsNull->context = null;
    $this->registry->add($returnsNull);

    $returnsContext = new FakeTenantResolutionStrategy();
    $returnsContext->context = $context;
    $this->registry->add($returnsContext);

    $resolved = makeResolver($this->registry)->resolve($this->tenantResolutionInput);

    expect($resolved)->toBe($context);
});

it('returns the first context when multiple strategies agree on the same tenant', function () {
    $first  = makeTenantContext();
    $second = makeTenantContext();

    $strategyA = new FakeTenantResolutionStrategy();
    $strategyA->context = $first;
    $this->registry->add($strategyA);

    $strategyB = new FakeTenantResolutionStrategy();
    $strategyB->context = $second;
    $this->registry->add($strategyB);

    $resolved = makeResolver($this->registry)->resolve($this->tenantResolutionInput);

    expect($resolved)->toBe($first);
});

it('throws TenantResolutionConflictException when no strategy resolves', function () {
    $strategy = new FakeTenantResolutionStrategy();
    $strategy->context = null;
    $this->registry->add($strategy);

    expect(fn () => makeResolver($this->registry)->resolve($this->tenantResolutionInput))
        ->toThrow(TenantResolutionConflictException::class);
});

it('throws TenantResolutionConflictException when the registry is empty', function () {
    $resolver = new ChainTenantResolver(new TenantResolverRegistry());

    expect(fn () => $resolver->resolve($this->tenantResolutionInput))
        ->toThrow(TenantResolutionConflictException::class);
});

it('throws TenantResolutionConflictException when strategies resolve to different tenants', function () {
    $strategyA = new FakeTenantResolutionStrategy();
    $strategyA->context = makeTenantContext();
    $this->registry->add($strategyA);

    $strategyB = new FakeTenantResolutionStrategy();
    $strategyB->context = new TenantContext(makeTenantRecord(id: 99), TenantResolutionSource::Header);
    $this->registry->add($strategyB);

    expect(fn () => makeResolver($this->registry)->resolve($this->tenantResolutionInput))
        ->toThrow(TenantResolutionConflictException::class);
});
