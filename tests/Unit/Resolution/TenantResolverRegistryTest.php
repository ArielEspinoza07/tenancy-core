<?php

declare(strict_types=1);

use Tenancy\Resolution\TenantResolverRegistry;
use Tenancy\Tests\Fixtures\FakeTenantResolutionStrategy;

beforeEach(function () {
    $this->registry = new TenantResolverRegistry();
});

it('starts empty', function () {
    expect($this->registry->all())->toBe([])
        ->and($this->registry->ordered())->toBe([])
        ->and($this->registry->strategies())->toBe([]);
});

it('add() stores an entry and returns itself for chaining', function () {
    $strategy = new FakeTenantResolutionStrategy();

    $this->registry->add($strategy, priority: 5);

    expect($this->registry->all())->toHaveCount(1)
        ->and(array_first($this->registry->all())->strategy)->toBe($strategy)
        ->and(array_first($this->registry->all())->priority)->toBe(5);
});

it('add() defaults priority to zero', function () {
    $this->registry->add(new FakeTenantResolutionStrategy());

    expect(array_first($this->registry->all())->priority)->toBe(0);
});

it('ordered() returns strategies sorted by priority descending', function () {
    $low    = new FakeTenantResolutionStrategy();
    $high   = new FakeTenantResolutionStrategy();
    $medium = new FakeTenantResolutionStrategy();

    $this->registry->add($low, priority: 1);
    $this->registry->add($high, priority: 100);
    $this->registry->add($medium, priority: 50);

    $ordered = $this->registry->ordered();

    expect($ordered[0]->strategy)->toBe($high)
        ->and($ordered[1]->strategy)->toBe($medium)
        ->and($ordered[2]->strategy)->toBe($low);
});

it('all() returns entries in insertion order regardless of priority', function () {
    $first  = new FakeTenantResolutionStrategy();
    $second = new FakeTenantResolutionStrategy();

    $this->registry->add($first, priority: 100);
    $this->registry->add($second, priority: 1);

    expect($this->registry->all()[0]->strategy)->toBe($first)
        ->and($this->registry->all()[1]->strategy)->toBe($second);
});

it('strategies() returns the strategy objects in priority order', function () {
    $low  = new FakeTenantResolutionStrategy();
    $high = new FakeTenantResolutionStrategy();

    $this->registry->add($low, priority: 1);
    $this->registry->add($high, priority: 10);

    expect($this->registry->strategies()[0])->toBe($high)
        ->and($this->registry->strategies()[1])->toBe($low);
});

it('ordered() does not mutate the original entries order', function () {
    $first  = new FakeTenantResolutionStrategy();
    $second = new FakeTenantResolutionStrategy();

    $this->registry->add($first, priority: 1);
    $this->registry->add($second, priority: 100);

    $this->registry->ordered();

    expect(array_first($this->registry->all())->strategy)->toBe($first);
});
