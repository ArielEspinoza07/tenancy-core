<?php

declare(strict_types=1);

use Tenancy\Enums\TenantResolutionSource;
use Tenancy\Enums\TenantStatus;
use Tenancy\Exceptions\TenantNotFoundException;
use Tenancy\Exceptions\TenantSuspendedException;
use Tenancy\Resolution\Strategies\CustomDomainTenantResolutionStrategy;
use Tenancy\Resolution\TenantResolutionInput;
use Tenancy\Support\HostNormalizer;
use Tenancy\Tests\Fixtures\FakeTenantLookup;

beforeEach(function () {
    $this->lookup = new FakeTenantLookup();
    $this->normalizer = new HostNormalizer();
    $this->strategy = new CustomDomainTenantResolutionStrategy(
        repository: $this->lookup,
        hostNormalizer: $this->normalizer,
        platformDomains: ['app.example.com'],
    );
});

// --- supports() ---

it('supports() returns true for a custom domain', function () {
    $input = TenantResolutionInput::fromArray(['host' => 'client.com']);

    expect($this->strategy->supports($input))->toBeTrue();
});

it('supports() returns false when host is null', function () {
    $input = TenantResolutionInput::fromArray([]);

    expect($this->strategy->supports($input))->toBeFalse();
});

it('supports() returns false when host is blank', function () {
    $input = TenantResolutionInput::fromArray(['host' => '   ']);

    expect($this->strategy->supports($input))->toBeFalse();
});

it('supports() returns false for an exact platform domain match', function () {
    $input = TenantResolutionInput::fromArray(['host' => 'app.example.com']);

    expect($this->strategy->supports($input))->toBeFalse();
});

it('supports() returns false for a subdomain of a platform domain', function () {
    $input = TenantResolutionInput::fromArray(['host' => 'tenant.app.example.com']);

    expect($this->strategy->supports($input))->toBeFalse();
});

it('supports() ignores blank entries in the platform domains list', function () {
    $strategy = new CustomDomainTenantResolutionStrategy(
        repository: $this->lookup,
        hostNormalizer: $this->normalizer,
        platformDomains: ['', 'app.example.com'],
    );
    $input = TenantResolutionInput::fromArray(['host' => 'client.com']);

    expect($strategy->supports($input))->toBeTrue();
});

it('supports() returns true when no platform domains are configured', function () {
    $strategy = new CustomDomainTenantResolutionStrategy($this->lookup, $this->normalizer);
    $input = TenantResolutionInput::fromArray(['host' => 'anything.com']);

    expect($strategy->supports($input))->toBeTrue();
});

// --- resolve() ---

it('resolve() returns null when host is null', function () {
    $input = TenantResolutionInput::fromArray([]);

    expect($this->strategy->resolve($input))->toBeNull();
});

it('resolve() returns null when host is a platform domain', function () {
    $input = TenantResolutionInput::fromArray(['host' => 'app.example.com']);

    expect($this->strategy->resolve($input))->toBeNull();
});

it('resolve() throws TenantNotFoundException when domain is unregistered and throwWhenUnregistered is true', function () {
    $this->lookup->record = null;
    $input = TenantResolutionInput::fromArray(['host' => 'unknown.com']);

    expect(fn () => $this->strategy->resolve($input))->toThrow(TenantNotFoundException::class);
});

it('resolve() returns null when domain is unregistered and throwWhenUnregistered is false', function () {
    $this->lookup->record = null;
    $strategy = new CustomDomainTenantResolutionStrategy(
        repository: $this->lookup,
        hostNormalizer: $this->normalizer,
        throwWhenUnregistered: false,
    );
    $input = TenantResolutionInput::fromArray(['host' => 'unknown.com']);

    expect($strategy->resolve($input))->toBeNull();
});

it('resolve() throws TenantSuspendedException when tenant is suspended', function () {
    $this->lookup->record = makeTenantRecord(TenantStatus::Suspended);
    $input = TenantResolutionInput::fromArray(['host' => 'client.com']);

    expect(fn () => $this->strategy->resolve($input))->toThrow(TenantSuspendedException::class);
});

it('resolve() throws TenantNotFoundException when tenant is not active', function (TenantStatus $status) {
    $this->lookup->record = makeTenantRecord($status);
    $input = TenantResolutionInput::fromArray(['host' => 'client.com']);

    expect(fn () => $this->strategy->resolve($input))->toThrow(TenantNotFoundException::class);
})->with([TenantStatus::Pending, TenantStatus::Deleted]);

it('resolve() returns a TenantContext with CustomDomain source', function () {
    $record = makeTenantRecord();
    $this->lookup->record = $record;
    $input = TenantResolutionInput::fromArray(['host' => 'client.com']);

    $context = $this->strategy->resolve($input);

    expect($context->record)->toBe($record)
        ->and($context->source)->toBe(TenantResolutionSource::CustomDomain);
});
