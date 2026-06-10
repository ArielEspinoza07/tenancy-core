<?php

declare(strict_types=1);

use Tenancy\Enums\TenantResolutionSource;
use Tenancy\Enums\TenantStatus;
use Tenancy\Exceptions\TenantNotFoundException;
use Tenancy\Exceptions\TenantSuspendedException;
use Tenancy\Resolution\Strategies\SubdomainTenantResolutionStrategy;
use Tenancy\Resolution\TenantResolutionInput;
use Tenancy\Support\HostNormalizer;
use Tenancy\Tests\Fixtures\FakeHostNormalizer;
use Tenancy\Tests\Fixtures\FakeTenantLookup;

beforeEach(function () {
    $this->lookup = new FakeTenantLookup();
    $this->normalizer = new HostNormalizer();
    $this->strategy = new SubdomainTenantResolutionStrategy(
        repository: $this->lookup,
        hostNormalizer: $this->normalizer,
        baseDomain: 'example.com',
    );
});

// --- supports() ---

it('supports() returns true for a valid tenant subdomain', function () {
    $input = TenantResolutionInput::fromArray(['host' => 'acme.example.com']);

    expect($this->strategy->supports($input))->toBeTrue();
});

it('supports() returns false when host is null', function () {
    $input = TenantResolutionInput::fromArray([]);

    expect($this->strategy->supports($input))->toBeFalse();
});

it('supports() returns false for the bare base domain', function () {
    $input = TenantResolutionInput::fromArray(['host' => 'example.com']);

    expect($this->strategy->supports($input))->toBeFalse();
});

it('supports() returns false for an unrelated domain', function () {
    $input = TenantResolutionInput::fromArray(['host' => 'other.com']);

    expect($this->strategy->supports($input))->toBeFalse();
});

it('supports() returns false for a reserved subdomain', function (string $host) {
    $input = TenantResolutionInput::fromArray(['host' => $host]);

    expect($this->strategy->supports($input))->toBeFalse();
})->with([
    'www.example.com',
    'app.example.com',
    'admin.example.com',
    'api.example.com',
]);

it('supports() returns false for a nested subdomain', function () {
    $input = TenantResolutionInput::fromArray(['host' => 'a.b.example.com']);

    expect($this->strategy->supports($input))->toBeFalse();
});

it('supports() returns true for a custom reserved subdomain list', function () {
    $strategy = new SubdomainTenantResolutionStrategy(
        repository: $this->lookup,
        hostNormalizer: $this->normalizer,
        baseDomain: 'example.com',
        reservedSubdomains: [],
    );
    $input = TenantResolutionInput::fromArray(['host' => 'www.example.com']);

    expect($strategy->supports($input))->toBeTrue();
});

// --- resolve() ---

it('resolve() returns null when host is null', function () {
    $input = TenantResolutionInput::fromArray([]);

    expect($this->strategy->resolve($input))->toBeNull();
});

it('resolve() returns null when called directly with a host that yields no subdomain', function () {
    $input = TenantResolutionInput::fromArray(['host' => 'www.example.com']);

    expect($this->strategy->resolve($input))->toBeNull();
});

it('resolve() returns null when the extracted subdomain is empty', function () {
    $strategy = new SubdomainTenantResolutionStrategy(
        repository: $this->lookup,
        hostNormalizer: new FakeHostNormalizer(),
        baseDomain: 'example.com',
    );
    $input = TenantResolutionInput::fromArray(['host' => '.example.com']);

    expect($strategy->resolve($input))->toBeNull();
});

it('resolve() throws TenantNotFoundException when tenant is not found', function () {
    $this->lookup->record = null;
    $input = TenantResolutionInput::fromArray(['host' => 'unknown.example.com']);

    expect(fn () => $this->strategy->resolve($input))->toThrow(TenantNotFoundException::class);
});

it('resolve() throws TenantSuspendedException when tenant is suspended', function () {
    $this->lookup->record = makeTenantRecord(TenantStatus::Suspended);
    $input = TenantResolutionInput::fromArray(['host' => 'acme.example.com']);

    expect(fn () => $this->strategy->resolve($input))->toThrow(TenantSuspendedException::class);
});

it('resolve() throws TenantNotFoundException when tenant is not active', function (TenantStatus $status) {
    $this->lookup->record = makeTenantRecord($status);
    $input = TenantResolutionInput::fromArray(['host' => 'acme.example.com']);

    expect(fn () => $this->strategy->resolve($input))->toThrow(TenantNotFoundException::class);
})->with([TenantStatus::Pending, TenantStatus::Deleted]);

it('resolve() returns a TenantContext with Subdomain source', function () {
    $record = makeTenantRecord();
    $this->lookup->record = $record;
    $input = TenantResolutionInput::fromArray(['host' => 'acme.example.com']);

    $context = $this->strategy->resolve($input);

    expect($context->record)->toBe($record)
        ->and($context->source)->toBe(TenantResolutionSource::Subdomain);
});

it('resolve() is case-insensitive for the subdomain', function () {
    $this->lookup->record = makeTenantRecord();
    $input = TenantResolutionInput::fromArray(['host' => 'ACME.EXAMPLE.COM']);

    expect($this->strategy->resolve($input))->not->toBeNull();
});
