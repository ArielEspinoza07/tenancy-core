<?php

declare(strict_types=1);

use Tenancy\Enums\TenantResolutionSource;
use Tenancy\Enums\TenantStatus;
use Tenancy\Exceptions\TenantNotFoundException;
use Tenancy\Exceptions\TenantSuspendedException;
use Tenancy\Resolution\Strategies\SessionTenantResolutionStrategy;
use Tenancy\Resolution\TenantResolutionInput;
use Tenancy\Tests\Fixtures\FakeTenantLookup;

beforeEach(function () {
    $this->lookup = new FakeTenantLookup();
    $this->strategy = new SessionTenantResolutionStrategy($this->lookup);
});

// --- supports() ---

it('supports() returns true when sessionTenantId is a positive integer', function () {
    $input = TenantResolutionInput::fromArray(['sessionTenantId' => 1]);

    expect($this->strategy->supports($input))->toBeTrue();
});

it('supports() returns true when sessionTenantId is a non-empty string', function () {
    $input = TenantResolutionInput::fromArray(['sessionTenantId' => 'uuid-123']);

    expect($this->strategy->supports($input))->toBeTrue();
});

it('supports() returns false when sessionTenantId is null', function () {
    $input = TenantResolutionInput::fromArray([]);

    expect($this->strategy->supports($input))->toBeFalse();
});

it('supports() returns false when sessionTenantId is zero', function () {
    $input = TenantResolutionInput::fromArray(['sessionTenantId' => 0]);

    expect($this->strategy->supports($input))->toBeFalse();
});

it('supports() returns false when sessionTenantId is a negative integer', function () {
    $input = TenantResolutionInput::fromArray(['sessionTenantId' => -1]);

    expect($this->strategy->supports($input))->toBeFalse();
});

it('supports() returns false when sessionTenantId is an empty string', function () {
    $input = TenantResolutionInput::fromArray(['sessionTenantId' => '  ']);

    expect($this->strategy->supports($input))->toBeFalse();
});

// --- resolve() ---

it('resolve() returns null when sessionTenantId is absent', function () {
    $input = TenantResolutionInput::fromArray([]);

    expect($this->strategy->resolve($input))->toBeNull();
});

it('resolve() throws TenantNotFoundException when tenant is not found', function () {
    $this->lookup->record = null;
    $input = TenantResolutionInput::fromArray(['sessionTenantId' => 99]);

    expect(fn () => $this->strategy->resolve($input))->toThrow(TenantNotFoundException::class);
});

it('resolve() throws TenantSuspendedException when tenant is suspended', function () {
    $this->lookup->record = makeTenantRecord(TenantStatus::Suspended);
    $input = TenantResolutionInput::fromArray(['sessionTenantId' => 1]);

    expect(fn () => $this->strategy->resolve($input))->toThrow(TenantSuspendedException::class);
});

it('resolve() throws TenantNotFoundException when tenant is not active', function (TenantStatus $status) {
    $this->lookup->record = makeTenantRecord($status);
    $input = TenantResolutionInput::fromArray(['sessionTenantId' => 1]);

    expect(fn () => $this->strategy->resolve($input))->toThrow(TenantNotFoundException::class);
})->with([TenantStatus::Pending, TenantStatus::Deleted]);

it('resolve() returns a TenantContext with Session source', function () {
    $record = makeTenantRecord();
    $this->lookup->record = $record;
    $input = TenantResolutionInput::fromArray(['sessionTenantId' => 1]);

    $context = $this->strategy->resolve($input);

    expect($context->record)->toBe($record)
        ->and($context->source)->toBe(TenantResolutionSource::Session);
});

it('resolve() converts a numeric string sessionTenantId to an integer', function () {
    $this->lookup->record = makeTenantRecord();
    $input = TenantResolutionInput::fromArray(['sessionTenantId' => '42']);

    expect($this->strategy->resolve($input))->not->toBeNull();
});
