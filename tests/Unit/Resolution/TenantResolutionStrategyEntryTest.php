<?php

declare(strict_types=1);

use Tenancy\Resolution\Strategies\SubdomainTenantResolutionStrategy;
use Tenancy\Resolution\TenantResolutionStrategyEntry;
use Tenancy\Support\HostNormalizer;
use Tenancy\Tests\Fixtures\FakeTenantLookup;

it('stores the strategy and priority', function () {
    $strategy = new SubdomainTenantResolutionStrategy(
        repository: new FakeTenantLookup(),
        hostNormalizer: new HostNormalizer(),
        baseDomain: 'example.com',
    );

    $entry = new TenantResolutionStrategyEntry(
        strategy: $strategy,
        priority: 10,
    );

    expect($entry->strategy)->toBe($strategy)
        ->and($entry->priority)->toBe(10);
});

it('defaults priority to zero', function () {
    $strategy = new SubdomainTenantResolutionStrategy(
        repository: new FakeTenantLookup(),
        hostNormalizer: new HostNormalizer(),
        baseDomain: 'example.com',
    );

    $entry = new TenantResolutionStrategyEntry(strategy: $strategy);

    expect($entry->priority)->toBe(0);
});
